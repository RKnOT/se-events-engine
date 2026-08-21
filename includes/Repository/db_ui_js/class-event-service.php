<?php

class SE_Event_Service {

    public static function rename_event_type(bool $dry_run = true): string {

        SE_Event_Repository_js::init();

        $before = SE_Event_Repository_js::count_not_se_event();

        $output = "Vorher: $before\n\n";

        $preview = SE_Event_Repository_js::get_preview();

        $output .= "Preview:\n";

        foreach ($preview as $row) {
            $output .= "ID {$row->event_id} → {$row->event_type}\n";
        }

        if ($dry_run) {
            $output .= "\n[DRY RUN]";
            return $output;
        }

        $updated = SE_Event_Repository_js::update_event_type();

        $after = SE_Event_Repository_js::count_not_se_event();

        $output .= "\nGeändert: $updated\n";
        $output .= "Nachher: $after\n";

        return $output;
    }
}
