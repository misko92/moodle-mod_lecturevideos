# Lecture videos (mod_lecturevideos)

A Moodle activity that plays a lecture's videos as one playlist and records which parts of each video every student
actually watched.

- **One player with the list beside it** (stacked under the video on phones). Each video in the list shows a preview
  image, its length, its own progress bar and a ✓ once watched. Switching videos happens in place; `?video=N` links to
  a specific video.
- **Honest tracking.** Only parts that were really played count: skipping ahead leaves a gap and replaying adds time
  spent but no new progress. The server limits credit to real elapsed time (at most 2× playback speed), so faked or
  rapid-fire progress reports gain nothing. Students resume where they left off.
- **Completion**: every video watched at least X% (default 90%), or an average of X% across the videos. Optional
  gradebook item (off by default), optional "videos must be watched in order" and "no skipping ahead".
- **Progress report** for teachers: one row per student with the overall bar under the name; expand a student to see
  every video's watched parts, percentage, time spent and when it was last watched.
- Uploaded files only (MP4/H.264 recommended). The video length is read from the file, so no ffmpeg is needed on the
  server. Preview images are optional.
- Course reset clears progress; backup/restore and the Privacy API are supported.

## Adding many videos at once

```
php mod/lecturevideos/cli/add_videos.php --cmid=123 --dir=/path/to/lecture01
```

Adds every `.mp4/.m4v/.mov/.webm` in the folder in natural filename order, titled by file name. A same-named
`.jpg/.png/.webp` becomes the video's preview image. `--csv` takes `title,videopath[,posterpath]` rows instead; `-n`
does a dry run.

## Requirements

Moodle 5.2 or 5.3.

## Credits

Forked from [Video Playlist (mod_videoplaylist)](https://github.com/EduardoKrausME/moodle-mod_videoplaylist) 1.0.9
by Eduardo Kraus, GPL v3 or later. The watched-segment tracking design is his. This fork is maintained separately;
the history starts with an unmodified import of his release.

## License

GNU GPL v3 or later. See [LICENSE](LICENSE).
