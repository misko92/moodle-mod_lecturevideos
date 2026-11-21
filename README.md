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

## Subtitles

Each video can have WebVTT subtitle files, one per language, named after the language: `en.vtt`, `es.vtt`, `es_mx.vtt`, and so on. Upload them in the video's settings, or in bulk:

    php mod/lecturevideos/cli/add_captions.php --cmid=123 --dir=/path/to/captions

`add_captions.php` matches files to videos by the stored video file name (`1.01 History of the Atom.mp4` → `1.01 History of the Atom.en.vtt`, `1.01 History of the Atom.es.vtt`). `add_videos.php --dir` picks up `<name>.<lang>.vtt` files next to the videos as well.

Viewers get the subtitles in their own Moodle language switched on automatically (e.g. `es.vtt` for Spanish or Mexican Spanish users). English captions are available but start switched off. The player's CC button switches language or turns subtitles off, and that choice carries over to the next video.

## Links to a moment in a video

Add `&t=` to a video link to start there: `view.php?id=12&video=34&t=5:07` (seconds, `m:ss`,
`h:mm:ss` or `5m07s` all work). If the activity doesn't allow free seeking, students still can't
jump past what they have watched.

For links that must work on more than one site (for example in quiz question feedback that is
exported from one site and imported into another), give the activity an **ID number** in its
settings and link through `goto.php`, which finds the activity by ID number and the video by the
start of its title:

```
/mod/lecturevideos/goto.php?cm=lv-ap-31&v=31.02&t=5:07
```

opens the video whose title starts with "31.02" in the activity with ID number `lv-ap-31`, at 5:07,
in the first such activity the student can open.

## Requirements

Moodle 5.2 or 5.3.

## Credits

Forked from [Video Playlist (mod_videoplaylist)](https://github.com/EduardoKrausME/moodle-mod_videoplaylist) 1.0.9
by Eduardo Kraus, GPL v3 or later. The watched-segment tracking design is his. This fork is maintained separately;
the history starts with an unmodified import of his release.

## License

GNU GPL v3 or later. See [LICENSE](LICENSE).
