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
 * player.js
 *
 * @package   mod_videotrackerultra
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Player bootstrap and server-calculated status refresh.
 *
 * @module     mod_videotrackerultra/player
 */
define(['core/ajax', 'core/notification', 'local_video_bridge/progress'], function(Ajax, Notification, Progress) {
    const formatSeconds = (seconds) => {
        seconds = Math.max(0, Math.round(Number(seconds) || 0));
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remaining = seconds % 60;
        return (hours ? String(hours).padStart(2, '0') + ':' : '') +
            String(minutes).padStart(2, '0') + ':' + String(remaining).padStart(2, '0');
    };

    const refresh = (root, config) => {
        Ajax.call([{
            methodname: 'mod_videotrackerultra_get_status',
            args: {cmid: Number(config.cmid)}
        }])[0].then((result) => {
            const status = root.querySelector('[data-region="status"]');
            const percent = root.querySelector('[data-region="percent"]');
            const playback = root.querySelector('[data-region="playbacktime"]');
            const sessions = root.querySelector('[data-region="sessions"]');
            if (status) {
                status.textContent = result.statuslabel;
                status.dataset.status = result.status;
            }
            if (percent) {
                percent.textContent = Number(result.percent || 0).toFixed(1) + '%';
            }
            if (playback) {
                playback.textContent = formatSeconds(result.playbacktime);
            }
            if (sessions) {
                sessions.textContent = String(result.sessions || 0);
            }

            let rules = [];
            try {
                rules = JSON.parse(result.rulesjson || '[]');
            } catch (error) {
                rules = [];
            }
            rules.forEach((rule) => {
                const row = root.querySelector('[data-rule-key="' + CSS.escape(rule.key) + '"]');
                if (!row) {
                    return;
                }
                const resultElement = row.querySelector('[data-region="rule-result"]');
                const evidence = row.querySelector('[data-region="rule-evidence"]');
                if (resultElement) {
                    resultElement.textContent = String(rule.result || '').toUpperCase();
                    resultElement.dataset.result = rule.result;
                }
                if (evidence) {
                    evidence.textContent = rule.evidence || '';
                }
            });
            return result;
        }).catch(Notification.exception);
    };

    const initOne = (root) => {
        let config;
        try {
            config = JSON.parse(root.dataset.config || '{}');
        } catch (error) {
            Notification.exception(error);
            return;
        }

        const playerRoot = root.querySelector('[data-region="player"]');
        const player = config.player || {};
        if (!playerRoot || !player.adaptermodule) {
            return;
        }

        require([player.adaptermodule], (provider) => {
            if (!provider || typeof provider.create !== 'function') {
                Notification.exception(new Error('Invalid Video Bridge adapter.'));
                return;
            }
            Promise.resolve(provider.create(playerRoot, player)).then((adapter) => {
                Progress.attach(adapter, playerRoot, player);
                if (adapter && typeof adapter.onEnded === 'function') {
                    adapter.onEnded(() => window.setTimeout(() => refresh(root, config), 1500));
                }
                window.setInterval(() => refresh(root, config), 65000);
                return null;
            }).catch(Notification.exception);
        }, Notification.exception);
    };

    const init = () => {
        document.querySelectorAll('[data-region="videotrackerultra"]').forEach(initOne);
    };

    return {init: init};
});
