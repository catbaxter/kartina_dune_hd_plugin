<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/tv/default_channel.php';

///////////////////////////////////////////////////////////////////////////

class KtvChannel extends DefaultChannel
{
    private $has_archive;
    private $is_protected;
    private $buffering_ms;
    private $timeshift_hours;

    ///////////////////////////////////////////////////////////////////////

    public function __construct($id, $title, $icon_url,
        $has_archive, $is_protected, $buffering_ms, $timeshift_hours)
    {
        parent::__construct($id, $title, $icon_url, null);

        $this->has_archive = $has_archive;
        $this->is_protected = $is_protected;
        $this->buffering_ms = $buffering_ms;
        $this->timeshift_hours = $timeshift_hours;
    }

    ///////////////////////////////////////////////////////////////////////

    public function has_archive()
    { return $this->has_archive; }

    public function is_protected()
    { return $this->is_protected; }

    public function get_buffering_ms()
    { return $this->buffering_ms; }

    public function get_timeshift_hours()
    { return $this->timeshift_hours; }

    public function get_past_epg_days()
    { return 14; }

    public function get_future_epg_days()
    { return 7; }

    public function get_archive_past_sec()
    { return 14 * 86400; }

    public function get_archive_delay_sec()
    { return 31 * 60; }
}

///////////////////////////////////////////////////////////////////////////
?>
