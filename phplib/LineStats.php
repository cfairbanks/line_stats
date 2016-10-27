<?php

class LineStats {

    const OTHER_TYPE = 'other';

    private static $types = [
        'bash' => ['.sh'],
        'conf' => ['.conf'],
        'css' => ['.css', '.scss', '.less'],
        'csv' => ['.csv'],
        'flash' => ['.swf'],
        'images' => ['.bmp', '.gif', '.ico', '.jpg', '.png', '.svg'],
        'htaccess' => ['.htaccess'],
        'html' => ['.htm', '.html'],
        'java' => ['.java'],
        'js' => ['.js'],
        'json' => ['.json'],
        'markdown' => ['.md'],
        'mustache' => ['.mustache'],
        'php' => ['.php'],
        'python' => ['.py'],
        'ruby' => ['.erb', '.rb'],
        'scala' => ['.scala'],
        'smarty' => ['.tpl'],
        'sql' => ['.sql'],
        'text' => ['.txt'],
        'tsv' => ['.tsv'],
        'xml' => ['.xml'],
        'yaml' => ['.yaml', '.yml'],
    ];

    /** @var array string => int */
    private $lines_added = [];

    /** @var array string => int */
    private $lines_removed = [];

    /** @var int */
    private $total_added = 0;

    /** @var int */
    private $total_removed = 0;

    /** @var array */
    private $unknown_file_extensions = [];

    /**
     * @param string $user_name
     * @param int $start_timestamp
     */
    public function __construct($user_name, $start_timestamp) {
        $this->user_name = $user_name;
        $this->start_timestamp = $start_timestamp;
    }

    /**
     * @param string $git_log_line formatted like
     */
    public function increment($git_log_line) {
        if ($git_log_line) {
            list($added, $removed, $filename) = explode("\t", $git_log_line);

            $type = $this->getFileType($filename);

            $this->incrementAdded($type, $added);
            $this->incrementRemoved($type, $removed);

            $this->total_added += $added ? intval(trim($added)) : 0;
            $this->total_removed += $removed ? intval(trim($removed)) : 0;
        }
    }

    private function getFileType($filename) {
        foreach (self::$types as $type_name => $type_suffixes) {
            foreach ($type_suffixes as $type_suffix) {
                if (self::endsWith(trim($filename), $type_suffix)) {
                    return $type_name;
                }
            }
        }

        $file_path_parts = explode('/', $filename);

        if ($file_path_parts) {
            $last_part = $file_path_parts[count($file_path_parts) - 1];

            // TODO - track files with no extensions
            if (strpos($last_part, '.') !== false) {
                $file_parts = explode('.', $last_part);

                if ($file_parts) {
                    $extension = $file_parts[count($file_parts) - 1];

                    if (!in_array($extension, $this->unknown_file_extensions)) {
                        $this->unknown_file_extensions[] = $extension;
                    }
                }
            }
        }

        return self::OTHER_TYPE;
    }

    public function printShort() {
        $this->run();

        return sprintf(
            "%s\t%s\t%d\t%d\t%d\n",
            @$this->start_timestamp ? date('Y-m-d', $this->start_timestamp) : '',
            $this->user_name,
            $this->total_added,
            $this->total_removed,
            $this->total_added - $this->total_removed
        );
    }

    public function printLong() {
        $this->run();
        return $this->formatLong();
    }

    private function formatLong() {
        @$time_output = $this->start_timestamp ? date('Y-m-d', $this->start_timestamp) : 'the beginning of time';

        $output = "Changes by {$this->user_name} since {$time_output}:\n";

        $output .= $this->formatTypeHeader();

        $output .= $this->formatTypeLine('all', $this->total_added, $this->total_removed);

        foreach (array_keys(self::$types) as $type_name) {
            $added = isset($this->lines_added[$type_name]) ? $this->lines_added[$type_name] : 0;
            $removed = isset($this->lines_removed[$type_name]) ? $this->lines_removed[$type_name] : 0;

            if ($added || $removed) {
                $output .= $this->formatTypeLine($type_name, $added, $removed);
            }
        }

        if ($this->getOtherAdded() || $this->getOtherRemoved()) {
            $output .= $this->formatTypeLine(self::OTHER_TYPE, $this->getOtherAdded(), $this->getOtherRemoved());

            if ($this->unknown_file_extensions) {
                $output .= "\nother: *." . implode(', *.', $this->unknown_file_extensions) . "\n";
            }
        }

        $output .= "\n";

        return $output;
    }

    private function run() {
        @$date_argument = $this->start_timestamp ? '--since ' . date('Y-m-d', $this->start_timestamp) : '';

        $git_command = "git log --author='{$this->user_name}' --pretty=tformat: --numstat $date_argument";

        $git_log_output = shell_exec($git_command);

        foreach (explode("\n", $git_log_output) as $git_log_line) {
            $this->increment($git_log_line);
        }
    }

    private function incrementAdded($type, $value) {
        if ($value) {
            if (!isset($this->lines_added[$type])) {
                $this->lines_added[$type] = 0;
            }

            $this->lines_added[$type] += $value;
        }
    }

    private function incrementRemoved($type, $value) {
        if ($value) {
            if (!isset($this->lines_removed[$type])) {
                $this->lines_removed[$type] = 0;
            }

            $this->lines_removed[$type] += $value;
        }
    }

    /** @return string */
    private function formatTypeHeader() {
        return "\n" . "Type\tAdded\tRemoved\tTotal\n" . "----\t-----\t-------\t-----\n";
    }

    /**
     * @param string $type_name
     * @param int $added
     * @param int $removed
     * @return string
     */
    private function formatTypeLine($type_name, $added, $removed) {
        return sprintf("%s\t%d\t%d\t%d\n", $type_name, $added, $removed, $added - $removed);
    }

    /** @return int */
    private function getOtherAdded() {
        return isset($this->lines_added[self::OTHER_TYPE]) ? $this->lines_added[self::OTHER_TYPE] : 0;
    }

    /** @return int */
    private function getOtherRemoved() {
        return isset($this->lines_removed[self::OTHER_TYPE]) ? $this->lines_removed[self::OTHER_TYPE] : 0;
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    private static function endsWith($haystack, $needle) {
        return
            $needle === "" ||
            (
                ($temp = strlen($haystack) - strlen($needle)) >= 0 &&
                stripos($haystack, $needle, $temp) !== FALSE
            );
    }
}
