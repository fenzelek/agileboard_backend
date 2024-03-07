
@extends('emails.notifications.email')

<style type="text/css" rel="stylesheet" media="all">
    .report-tab th, .report-tab td{
        padding-bottom: 5px;
        padding-right: 5px;
        max-width: 640px;
    }
    .email-body_inner{
        max-width: none !important;
    }
    th{
        text-align: left !important;
    }
    th.date{
        min-width: 135px
    }
    tr td.row-separator{
        padding-bottom: 15px;
    }
    .pr {
        padding-right: 7px;
    }
</style>

@section('content')

    <h2> {{ __('notifications.daily_ticket_report.welcome', ['name' => $user->first_name]) }}</h2>

    <h4> {{ __('notifications.daily_ticket_report.report_date', ['date' => $date->toDateString()]) }}</h4>

    <table class="report-tab">

        @foreach($report_data as $group)
            @foreach($group as $row)
                <tr>
                    <td>
                        <div>
                            <span style="font-weight: 500">
                                <a href={{ config('app_settings.welcome_absolute_url') . '/projects/'.$row['ticket']['project_id'] . '/ticket/' . $row['ticket']['title'] }} target="_blank" style="color:black; font-size: 18px; text-decoration:none;">
                                    {{ $row['ticket']['title'] . " " . $row['ticket']['name']}}
                                </a>
                            </span>
                            <span style="float: right; color: #999; padding-right: 20px;">
                                {{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $row['created_at'], config('app.timezone'))->setTimezone('CET')->format('H:i') }}
                            </span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span>{{ substr($row['user']['first_name'], 0, 1) }}{{ substr($row['user']['last_name'], 0, 1) }}:</span>

                        <span class="pr" style="text-decoration: underline;">
                            @if ($row['field']['object_type'] == 'ticket_comment')
                                {{ __('history.tasks.field_types.' . $row['field']['object_type']) }}
                            @else
                                {{ __('history.tasks.field_names.' . $row['field']['field_name']) }}
                            @endif
                        </span>

                        @if ($row['field']['field_name'] == 'priority')
                            <span>
                                @if ($row['value_before'] > $row['value_after'])
                                    {{ __('history.tasks.priority.decreased') }}
                                @else
                                    {{ __('history.tasks.priority.increased') }}
                                @endif
                            </span>
                        @elseif ($row['field']['field_name'] == 'status_id')
                            <span class="pr"  style="text-decoration: line-through; color: grey;">{{ $project_statuses[$row['ticket']['project_id']][$row['value_before']]['name'] }}</span>
                            <span class="pr" >&#128073;</span>
                            <span style="font-weight: 600">{{ $project_statuses[$row['ticket']['project_id']][$row['value_after']]['name'] }}</span>
                        @elseif ($row['field']['field_name'] == 'created_at')

                            @if ($row['field']['object_type'] == 'ticket_comment')
                                @php
                                    $comment = App\Models\Db\TicketComment::where('created_at', $row['value_after'])->where('ticket_id', $row['ticket']['id'])->first();

                                    if (empty($comment)) {
                                        \Illuminate\Support\Facades\Log::alert('Daily report - comment missing', [
                                            'created_at' => $row['value_after'],
                                            'ticket_id' => $row['ticket']['id'],
                                        ]);
                                    } else {
                                        $comment = strip_tags($comment->text);
                                        $comment = html_entity_decode($comment);
                                        $comment = \Illuminate\Support\Str::limit($comment, 60);
                                    }
                                @endphp

                                @if(empty($comment))
                                    @continue
                                @endif

                                <span style="font-weight: 600">{{ $comment }}</span>
                            @else
                                <span></span>
                            @endif

                        @elseif ($row['field']['field_name'] == 'estimate_time')
                            <span class="pr"  style="text-decoration: line-through; color: grey;">{{ floor($row['value_before'] / 3600) . gmdate("\h i\m", $row['value_before'] % 3600) }}</span>
                            <span class="pr" >&#128073;</span>
                            <span style="font-weight: 600">{{ floor($row['value_after'] / 3600) . gmdate("\h i\m", $row['value_after'] % 3600) }}</span>
                        @elseif ($row['field']['field_name'] == 'description')
                            <span class="pr"  style="text-decoration: line-through; color: grey;">{{ \Illuminate\Support\Str::limit(strip_tags($row['value_before']), 100) }}</span>
                            <span class="pr" >&#128073;</span>
                            <span style="font-weight: 600">{{ \Illuminate\Support\Str::limit(strip_tags($row['value_after']), 100) }}</span>
                        @elseif ($row['field']['field_name'] == 'story_id')

                            @if ($row['value_before'])
                                <span class="pr"  style="text-decoration: line-through; color: grey;">{{ App\Models\Db\Story::find($row['value_before'])->name }}</span>
                                <span class="pr" >&#128073;</span>
                            @else
                                <span></span>
                            @endif

                            @if ($row['value_after'])
                                <span style="font-weight: 600">{{ App\Models\Db\Story::find($row['value_after'])->name }}</span>
                            @else
                                <span></span>
                            @endif
                        @elseif ($row['field']['field_name'] == 'assigned_id')

                            @if ($row['value_before'])
                                @php
                                    $user_before = App\Models\Db\User::find($row['value_before']);
                                @endphp
                                <span class="pr"  style="text-decoration: line-through; color: grey;">{{ $user_before->first_name }} {{ $user_before->last_name }}</span>
                                <span class="pr" >&#128073;</span>
                            @else
                                <span></span>
                            @endif

                            @if ($row['value_after'])
                                @php
                                    $user_after = App\Models\Db\User::find($row['value_after']);
                                @endphp
                                <span style="font-weight: 600">{{ $user_after->first_name }} {{ $user_after->last_name }}</span>
                            @else
                                <span></span>
                            @endif
                        @else

                            @if($row['field']['field_name'] == 'sprint_id')
                                @php
                                    $value_before = \App\Models\Db\Sprint::find($row['value_before'])->name ?? '';
                                    $value_after = \App\Models\Db\Sprint::find($row['value_after'])->name ?? '';
                                @endphp
                            @else
                                @php
                                    $value_before = $row['value_before'];
                                    $value_before = strip_tags($value_before);
                                    $value_before = html_entity_decode($value_before);

                                    $value_after = $row['value_after'];
                                    $value_after = strip_tags($value_after);
                                    $value_after = html_entity_decode($value_after);
                                @endphp
                            @endif

                            <span class="pr"  style="text-decoration: line-through; color: grey;">{{ $value_before }}</span>
                            <span class="pr" >&#128073;</span>
                            <span style="font-weight: 600">{{ $value_after }}</span>
                        @endif
                        <hr style="color:#fff">
                    </td>
                </tr>
            @endforeach
            <tr><td class="row-separator"></td></tr>
        @endforeach
    </table>

@endsection
