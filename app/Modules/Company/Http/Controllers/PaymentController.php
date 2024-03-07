<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Resources\Package;
use App\Models\Db\CompanyModule;
use App\Models\Db\CompanyModuleHistory;
use App\Models\Db\Module;
use App\Models\Db\ModuleMod;
use App\Models\Db\Transaction;
use App\Models\Other\PaymentStatus;
use App\Modules\Company\Events\PaymentCompleted;
use App\Modules\Company\Http\Requests\PaymentConfimBuy;
use App\Modules\Company\Http\Requests\PaymentsIndex;
use App\Modules\Company\Services\CompanyModuleUpdater;
use App\Modules\Company\Services\PaymentNotificationsService;
use App\Services\Paginator;
use Auth;
use App\Helpers\ApiResponse;
use App\Helpers\ErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Db\Payment;
use App\Models\Db\Subscription;
use App\Modules\Company\Services\PaymentService;
use App\Modules\Company\Services\PayU\ParamsFactory;
use App\Modules\Company\Services\PayU\PayU;
use App\Http\Resources\Payment as PaymentTransformer;
use App\Http\Resources\PaymentIndex as PaymentIndexTransformer;
use App\Http\Resources\Transaction as TransactionTransformer;
use App\Http\Resources\Package as PackageTransformer;
use App\Http\Resources\Module as ModuleTransformer;
use App\Http\Resources\ModuleMod as ModuleModTransformer;
use App\Http\Resources\CompanyModuleHistory as CompanyModuleHistoryTransformer;
use Illuminate\Contracts\Events\Dispatcher as Event;

class PaymentController extends Controller
{
    /**
     * List payments.
     *
     * @param PaymentsIndex $request
     * @param Payment $payment
     * @param Paginator $paginator
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(PaymentsIndex $request, Payment $payment, Paginator $paginator)
    {
        if ($request->input('status')) {
            $payment = $payment->where('status', $request->input('status'));
        }

        $payment = $payment->whereHas('transaction.companyModulesHistory', function ($q) {
            $q->where('company_id', auth()->user()->getSelectedCompanyId());
        })
            ->where('status', '!=', PaymentStatus::STATUS_BEFORE_START)
            ->orderByDesc('created_at')
            ->with('subscription');

        return ApiResponse::transResponseOk($paginator->get($payment, 'payments.index'), 200, [
            Payment::class => PaymentIndexTransformer::class,
        ]);
    }

    /**
     * Get payment.
     *
     * @param Payment $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Payment $payment)
    {
        $payment = $payment->load([
            'transaction.companyModulesHistory.module',
            'transaction.companyModulesHistory.moduleMod',
            'transaction.companyModulesHistory.package',
        ]);

        return ApiResponse::transResponseOk($payment, 200, [
            Payment::class => PaymentTransformer::class,
            Transaction::class => TransactionTransformer::class,
            Package::class => PackageTransformer::class,
            Module::class => ModuleTransformer::class,
            ModuleMod::class => ModuleModTransformer::class,
            CompanyModuleHistory::class => CompanyModuleHistoryTransformer::class,
        ]);
    }

    /**
     * @param PaymentConfimBuy $request
     * @param Payment $payment
     * @param Subscription $subscription
     * @param ParamsFactory $paramsFactory
     * @param PayU $payU
     * @param PaymentService $service
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function confirmBuy(PaymentConfimBuy $request, Payment $payment, Subscription $subscription, ParamsFactory $paramsFactory, PayU $payU, PaymentService $service)
    {
        //token
        $params = $request->all();
        if (isset($params['token'])) {
            try {
                $token_data = decrypt($params['token']);
                if ($token_data['id'] != auth()->id()) {
                    return ApiResponse::responseError(ErrorCode::PAYU_TECHNICAL_PROBLEMS, 409);
                }
                $params['token'] = $token_data['token'];
            } catch (\Exception $e) {
                return ApiResponse::responseError(ErrorCode::PAYU_TECHNICAL_PROBLEMS, 409);
            }
        }

        //order params
        $orderParams = $paramsFactory->createOrderParams($params, auth()->user(), $payment);
        $payU->setParams($orderParams);

        //run order
        $response = $service->proceed($payU, Auth::user(), $payment, $subscription, $params);

        //response
        if (! $response) {
            return ApiResponse::responseError(ErrorCode::PAYU_TECHNICAL_PROBLEMS, 409);
        }

        if ($response->isSuccess()) {
            return ApiResponse::responseOk([
                'redirect_url' => $response->getRedirectUrl(),
            ]);
        }
        if ($response->getError() == $response::WARNING_CONTINUE_3DS) {
            return ApiResponse::responseError(ErrorCode::PAYU_WARNING_CONTINUE_3DS, 409, [
                'redirect_url' => $response->getRedirectUrl(),
            ]);
        }
        if ($response->getError() == $response::WARNING_CONTINUE_CVV) {
            return ApiResponse::responseError(ErrorCode::PAYU_WARNING_CONTINUE_CVV, 409);
        }

        return ApiResponse::responseError(ErrorCode::PAYU_SOME_ERROR, 409);
    }

    /**
     * @param Transaction $transaction
     * @param PaymentService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function payAgain(Transaction $transaction, PaymentService $service)
    {
        $data = $service->payAgain($transaction);

        return ApiResponse::transResponseOk($data, 200, [Payment::class => PaymentIndexTransformer::class]);
    }

    /**
     * Get credit card list.
     *
     * @param PayU $payU
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function cardList(PayU $payU)
    {
        //todo get currency from request

        $payU->setUser(Auth::user());
        $return = $payU->getCardTokens('en', 'PLN');

        if (is_array($return)) {
            $cards = [];
            foreach ($return as $card) {
                if ($card->status == 'ACTIVE') {
                    $card->value = encrypt([
                        'id' => Auth::id(),
                        'token' => $card->value,
                    ]);
                    $cards [] = $card;
                }
            }

            return ApiResponse::responseOk($cards);
        }

        return ApiResponse::responseError(ErrorCode::PAYU_TECHNICAL_PROBLEMS, 409);
    }

    /**
     * Cancel subscription.
     *
     * @param Subscription $subscription
     * @param PaymentService $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSubscription(Subscription $subscription, PaymentService $service)
    {
        $service->cancelSubscription($subscription);

        return ApiResponse::responseOk();
    }

    /**
     * Cancel payments.
     *
     * @param Payment $payment
     * @param PayU $payU
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelPayment(Payment $payment, PayU $payU)
    {
        if ($payU->cancel($payment->id, $payment->currency)) {
            return ApiResponse::responseOk();
        }

        return ApiResponse::responseError(ErrorCode::PAYU_SOME_ERROR, 409);
    }

    /**
     * Callback from payU.
     *
     * @param $currency
     * @param Payment $payment
     * @param PayU $payU
     * @param PaymentNotificationsService $notificationsService
     * @param CompanyModuleUpdater $updater
     * @param CompanyModuleHistory $history
     * @param CompanyModule $companyModule
     * @param PaymentService $service
     * @param Event $event
     * @return \Illuminate\Http\JsonResponse
     * @throws \OpenPayU_Exception_Configuration
     */
    public function getNotification(
        $currency,
        Payment $payment,
        PayU $payU,
        PaymentNotificationsService $notificationsService,
        CompanyModuleUpdater $updater,
        CompanyModuleHistory $history,
        CompanyModule $companyModule,
        PaymentService $service,
        Event $event
    ) {
        if (! in_array($currency, ['pln', 'eur'])) {
            return ApiResponse::responseError(ErrorCode::RESOURCE_NOT_FOUND, 404);
        }

        $order = $payU->getDataFromNotification($currency);

        if ($order) {
            $payment = $payment->where('external_order_id', $order->order_id)
                ->where('status', '!=', PaymentStatus::STATUS_COMPLETED)
                ->first();

            if ($payment) {
                $payment->status = $order->status;
                $payment->save();

                if ($payment->status == PaymentStatus::STATUS_COMPLETED) {
                    $service->paymentCompleted($payment, $notificationsService, $updater, $history, $companyModule);
                    $owner = $payment->transaction->companyModulesHistory[0]->company->getOwners()[0];
                    $event->dispatch(new PaymentCompleted($owner->user, $payment));
                }

                if ($payment->status == PaymentStatus::STATUS_CANCELED) {
                    $notificationsService->paymentStatusInfo($payment);
                }

                if ($payment->status == PaymentStatus::STATUS_REJECTED) {
                    $payU->setOrderCompleted($order->order_id, $payment->currency);
                }
            }
        }

        return ApiResponse::responseOk();
    }
}
