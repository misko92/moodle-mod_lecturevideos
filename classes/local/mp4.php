<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_lecturevideos\local;

/**
 * Minimal MP4/MOV header reader (duration only), so no ffmpeg is needed on the server.
 *
 * @package   mod_lecturevideos
 * @copyright 2026 misko92
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mp4 {
    /**
     * Duration in seconds of an MP4/MOV stored file, or 0 when it cannot be read.
     *
     * @param \stored_file $file
     * @return float
     */
    public static function duration(\stored_file $file): float {
        $fh = $file->get_content_file_handle();
        if (!$fh) {
            return 0.0;
        }
        try {
            $moov = self::find($fh, 0, $file->get_filesize(), 'moov');
            if (!$moov) {
                return 0.0;
            }
            $mvhd = self::find($fh, $moov[0], $moov[1], 'mvhd');
            if (!$mvhd) {
                return 0.0;
            }
            fseek($fh, $mvhd[0]);
            $version = ord(fread($fh, 1));
            if ($version === 1) {
                fseek($fh, $mvhd[0] + 20);
                $d = unpack('Nscale/Jdur', fread($fh, 12));
            } else {
                fseek($fh, $mvhd[0] + 12);
                $d = unpack('Nscale/Ndur', fread($fh, 8));
            }
            return $d['scale'] > 0 ? round($d['dur'] / $d['scale'], 3) : 0.0;
        } finally {
            fclose($fh);
        }
    }

    /**
     * Find a box inside [$start, $end); returns [content start, box end] or null.
     *
     * @param resource $fh
     * @param int $start
     * @param int $end
     * @param string $type
     * @return array|null
     */
    private static function find($fh, int $start, int $end, string $type): ?array {
        $pos = $start;
        while ($pos + 8 <= $end) {
            fseek($fh, $pos);
            $head = fread($fh, 16);
            if (strlen($head) < 8) {
                return null;
            }
            $size = unpack('N', substr($head, 0, 4))[1];
            $name = substr($head, 4, 4);
            $headlen = 8;
            if ($size === 1) {
                $size = unpack('J', substr($head, 8, 8))[1];
                $headlen = 16;
            } else if ($size === 0) {
                $size = $end - $pos;
            }
            if ($size < 8) {
                return null;
            }
            if ($name === $type) {
                return [$pos + $headlen, $pos + $size];
            }
            $pos += $size;
        }
        return null;
    }
}
