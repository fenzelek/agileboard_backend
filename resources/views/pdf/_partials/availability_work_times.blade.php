<style>
    .work_time {
        color: green;
        font-weight: bold;
    }

    .work_time_weekday {
        color: blue;
        font-weight: bold;
    }

    .work_time_weekend {
        color: purple;
        font-weight: bold;
    }
</style>
<span class="work_time">
    {{ floor($user['months'][$int_month]['timestamp'] / 3600) . ':' . ($user['months'][$int_month]['timestamp'] / 60) % 60 }}h
</span>
<br>
<span class="work_time_weekday">
    @lang('Work time in weekdays'):
    {{ floor($user['months'][$int_month]['timestamp_week_days'] / 3600) . ':' . ($user['months'][$int_month]['timestamp_week_days'] / 60) % 60 }}h
</span>
<br>
<span class="work_time_weekend">
    @lang('Work time in saturdays'):
    {{ floor($user['months'][$int_month]['timestamp_saturdays'] / 3600) . ':' . ($user['months'][$int_month]['timestamp_saturdays'] / 60) % 60 }}h
</span>
<br>
<span class="work_time_weekend">
    @lang('Work time in sundays'):
    {{ floor($user['months'][$int_month]['timestamp_sundays'] / 3600) . ':' . ($user['months'][$int_month]['timestamp_sundays'] / 60) % 60 }}h
</span>
<br>