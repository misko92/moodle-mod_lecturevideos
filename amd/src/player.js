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

/**
 * One HTML5 player with an in-page playlist; reports the parts actually watched.
 *
 * Based on the tracker of mod_videoplaylist by Eduardo Kraus. Changes: in-place video switching,
 * an opening heartbeat so the first seconds are credited, the segment before a seek is kept,
 * requests are sent one at a time in order, and progress is flushed when the page is hidden.
 *
 * @module     mod_lecturevideos/player
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @copyright  2026 misko92
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {getString} from 'core/str';

const HEARTBEAT = 5;

const randomKey = () => Array.from(crypto.getRandomValues(new Uint8Array(24)))
    .map(v => v.toString(16).padStart(2, '0')).join('');

const parseSegments = text => {
    try {
        const s = JSON.parse(text || '[]');
        return Array.isArray(s) ? s : [];
    } catch (e) {
        return [];
    }
};

/**
 * Seconds watched without a gap from the start (used when skipping ahead is not allowed).
 *
 * @param {Array} segments
 * @returns {number}
 */
const frontier = segments => {
    let end = 0;
    segments.slice().sort((a, b) => a[0] - b[0]).forEach(s => {
        if (s[0] <= end + 1) {
            end = Math.max(end, s[1]);
        }
    });
    return end;
};

const renderTimeline = (el, segments, duration) => {
    if (!el) {
        return;
    }
    el.replaceChildren();
    if (!duration) {
        return;
    }
    segments.forEach(s => {
        const span = document.createElement('span');
        span.style.left = (100 * s[0] / duration) + '%';
        span.style.width = (100 * (s[1] - s[0]) / duration) + '%';
        el.append(span);
    });
};

export const init = () => {
    const root = document.querySelector('[data-region="lecturevideos"]');
    const video = root ? root.querySelector('video.lv-video') : null;
    if (!video) {
        return;
    }
    const cmid = Number(root.dataset.cmid);
    const allowseek = root.dataset.allowseek === '1';
    const items = Array.from(root.querySelectorAll('[data-region="item"]'));
    let item = items.find(b => b.classList.contains('active'));
    let completedText = '';
    getString('statuscompleted', 'mod_lecturevideos').then(s => {
        completedText = s;
        return s;
    }).catch(() => null);

    // Tracking state for the current video.
    let sessionkey = '';
    let seq = 0;
    let segStart = null;
    let lastTime = 0;
    let lastSent = 0;
    let maxSeen = 0;
    let queue = Promise.resolve();

    const updateUI = (target, data) => {
        const pct = Math.round(Number(data.percent));
        target.dataset.percent = data.percent;
        target.dataset.segments = data.segments;
        target.dataset.lastposition = data.lastposition;
        const bar = target.querySelector('[data-region="item-bar"]');
        if (bar) {
            bar.style.width = pct + '%';
        }
        const status = target.querySelector('[data-region="item-status"]');
        if (status) {
            status.textContent = Number(data.percent) >= Number(target.dataset.minpercent)
                ? '✓ ' + completedText : pct + '%';
        }
        const overall = Math.round(Number(data.overallpercent));
        const op = root.querySelector('[data-region="overall-percent"]');
        if (op) {
            op.textContent = overall + '%';
            root.querySelector('[data-region="overall-bar"]').style.width = overall + '%';
            root.querySelector('[data-region="overall-progress"]').setAttribute('aria-valuenow', overall);
        }
        if (target === item) {
            root.querySelector('[data-region="video-percent"]').textContent = pct + '%';
            renderTimeline(root.querySelector('[data-region="timeline"]'), parseSegments(data.segments), video.duration);
        }
    };

    /**
     * Queue one report. Reports go out one at a time so the server sees them in order.
     *
     * @param {number} start
     * @param {number} end
     * @param {number} position
     */
    const report = (start, end, position) => {
        if (!item || !video.duration || !isFinite(video.duration)) {
            return;
        }
        const target = item;
        const args = {
            cmid: cmid, videoid: Number(target.dataset.id), currentposition: position, duration: video.duration,
            segmentstart: start, segmentend: Math.max(start, end), playbackrate: video.playbackRate || 1,
            sequence: ++seq, sessionkey: sessionkey,
        };
        lastSent = position;
        queue = queue.then(() => Ajax.call([{methodname: 'mod_lecturevideos_update_progress', args: args}])[0])
            .then(data => {
                updateUI(target, data);
                return data;
            })
            .catch(() => null);
    };

    /** Send the watched stretch so far (if any) and keep tracking from here. */
    const flush = () => {
        if (segStart !== null) {
            report(segStart, lastTime, lastTime);
            segStart = video.paused ? null : lastTime;
        }
    };

    const startSession = () => {
        sessionkey = randomKey();
        seq = 0;
        segStart = null;
        lastSent = 0;
        lastTime = video.currentTime || 0;
        maxSeen = frontier(parseSegments(item.dataset.segments));
    };

    const load = (target, autoplay) => {
        if (!target || !target.dataset.src) {
            // Locked when the page loaded but possibly unlocked now: reload on that video.
            if (target) {
                const url = new URL(window.location.href);
                url.searchParams.set('video', target.dataset.id);
                window.location.assign(url.toString());
            }
            return;
        }
        flush();
        item = target;
        items.forEach(b => {
            b.classList.toggle('active', b === target);
            if (b === target) {
                b.setAttribute('aria-current', 'true');
            } else {
                b.removeAttribute('aria-current');
            }
        });
        if (target.dataset.poster) {
            video.poster = target.dataset.poster;
        } else {
            video.removeAttribute('poster');
        }
        video.src = target.dataset.src;
        root.querySelector('[data-region="title"]').textContent = target.dataset.title;
        root.querySelector('[data-region="description"]').textContent = target.dataset.description || '';
        root.querySelector('[data-region="video-percent"]').textContent = Math.round(Number(target.dataset.percent)) + '%';
        renderTimeline(root.querySelector('[data-region="timeline"]'), [], 0);
        startSession();
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('video', target.dataset.id);
            window.history.replaceState(null, '', url.toString());
        } catch (e) {
            // Ignore: deep links are a nicety.
        }
        if (autoplay) {
            video.play().catch(() => null);
        }
    };

    video.addEventListener('loadedmetadata', () => {
        const resume = Number(item.dataset.lastposition || 0);
        if (resume > 0 && resume < video.duration - 5) {
            video.currentTime = resume;
        }
        lastTime = video.currentTime;
        renderTimeline(root.querySelector('[data-region="timeline"]'), parseSegments(item.dataset.segments),
            video.duration);
    });

    video.addEventListener('play', () => {
        segStart = video.currentTime;
        lastTime = video.currentTime;
    });

    // Opening heartbeat once playback really starts (the length is known by then, also right after
    // switching videos): starts the server-side session clock so the first seconds are credited.
    video.addEventListener('playing', () => {
        if (seq === 0 && video.duration) {
            report(video.currentTime, video.currentTime, video.currentTime);
        }
    });

    video.addEventListener('timeupdate', () => {
        if (video.seeking) {
            return;
        }
        lastTime = video.currentTime;
        maxSeen = Math.max(maxSeen, lastTime);
        if (segStart !== null && Math.abs(lastTime - lastSent) >= HEARTBEAT) {
            report(segStart, lastTime, lastTime);
            segStart = lastTime;
        }
    });

    video.addEventListener('pause', () => {
        if (!video.seeking) {
            flush();
        }
    });

    video.addEventListener('seeking', () => {
        if (!allowseek && video.currentTime > maxSeen + 3) {
            video.currentTime = maxSeen;
            return;
        }
        // Keep what was watched before the jump, then continue from the new position.
        if (segStart !== null && lastTime > segStart) {
            report(segStart, lastTime, lastTime);
        }
        segStart = video.paused ? null : video.currentTime;
        lastTime = video.currentTime;
    });

    video.addEventListener('seeked', () => {
        lastTime = video.currentTime;
        if (!video.paused) {
            segStart = video.currentTime;
        }
    });

    video.addEventListener('ended', () => {
        lastTime = video.duration;
        flush();
        segStart = null;
        const next = items[items.indexOf(item) + 1];
        if (next) {
            // Give the last report a moment so a sequential playlist can unlock the next video.
            queue.then(() => load(next, true)).catch(() => null);
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            flush();
        }
    });
    window.addEventListener('pagehide', flush);

    items.forEach(b => b.addEventListener('click', () => {
        if (b !== item) {
            load(b, true);
            video.scrollIntoView({behavior: 'smooth', block: 'nearest'});
        }
    }));

    // Keep the list as tall as the player when they sit side by side.
    const list = root.querySelector('[data-region="list"]');
    const fit = () => {
        list.style.maxHeight = window.matchMedia('(min-width: 992px)').matches
            ? video.getBoundingClientRect().height + 'px' : '';
    };
    window.addEventListener('resize', fit);
    video.addEventListener('loadedmetadata', fit);
    fit();

    startSession();
};
