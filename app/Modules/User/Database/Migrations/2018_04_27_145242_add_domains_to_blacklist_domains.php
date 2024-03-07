<?php

use App\Models\Db\BlacklistDomain;
use Illuminate\Database\Migrations\Migration;

class AddDomainsToBlacklistDomains extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $list = [
            'mailinator.pl',
            'mailinator2.com',
            'niepodam.pl',
            'nie-podam.pl',
            'podam.pl',
            'koszmail.pl',
            '10minut.xyz',
            'temp-mail.org',
            '0nly.org',
            'emailna.co',
            'giyam.com',
            'dwango.ml',
            'asdf.pl',
            '10minut.com.pl',
            'yopmail.fr',
            'yopmail.net',
            'cool.fr.nf',
            'jetable.fr.nf',
            'nospam.ze.tc',
            'nomail.xl.cx',
            'mega.zik.dj',
            'speed.1s.fr',
            'courriel.fr.nf',
            'moncourrier.fr.nf',
            'monemail.fr.nf',
            'monmail.fr.nf',
            'tutye.com',
            'imgof.com',
            'wimsg.com',
        ];

        DB::transaction(function () use ($list) {
            foreach ($list as $domain) {
                BlacklistDomain::create(['domain' => $domain]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
