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
 * Client-side controller for the Socratic chat activity.
 *
 * @module     mod_socratic/chat
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/ajax',
    'core/templates',
    'core/notification',
], function(Ajax, Templates, Notification) {

    let root;
    let config;
    let busy = false;
    let pending = null;

    const makeClientId = () => `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 12)}`;

    const setBusy = (state) => {
        busy = state;
        root.querySelectorAll('[data-action="send"], [data-action="hint"], [data-action="final"]')
            .forEach((button) => {
                button.disabled = state || Boolean(pending);
            });

        const end = root.querySelector('[data-action="end"]');
        if (end) {
            end.disabled = state;
        }
    };

    const scrollMessages = () => {
        const messages = root.querySelector('[data-region="messages"]');
        messages.scrollTop = messages.scrollHeight;
    };

    const appendMessage = async (isuser, content, clientid = '') => {
        const result = await Templates.renderForPromise('mod_socratic/message', {
            isuser,
            isassistant: !isuser,
            content,
            time: '',
        });

        const region = root.querySelector('[data-region="messages"]');
        Templates.appendNodeContents(region, result.html, result.js);

        const node = region.lastElementChild;
        if (node && clientid) {
            node.dataset.clientid = clientid;
        }

        scrollMessages();
        return node;
    };

    const clearError = () => {
        const box = root.querySelector('[data-region="send-error"]');
        box.classList.add('d-none');
        box.querySelector('[data-action="retry"]').classList.add('d-none');
    };

    const showError = (message, retryable) => {
        const box = root.querySelector('[data-region="send-error"]');
        box.querySelector('[data-region="send-error-text"]').textContent = message;
        box.classList.remove('d-none');
        box.querySelector('[data-action="retry"]').classList.toggle('d-none', !retryable);
    };

    const markCompleted = () => {
        root.querySelector('[data-region="composer"]').classList.add('d-none');
        root.querySelector('[data-region="completed-message"]').classList.remove('d-none');
    };

    const updateCount = (used, max) => {
        root.querySelector('[data-region="interaction-count"]').textContent = `${used} / ${max}`;
    };

    const requestTurn = async (turn, appendUser = true) => {
        if (busy) {
            return;
        }

        clearError();
        setBusy(true);

        let provisional = null;

        try {
            if (appendUser) {
                provisional = await appendMessage(true, turn.display, turn.clientid);
            }

            const result = await Ajax.call([{
                methodname: 'mod_socratic_send_message',
                args: {
                    cmid: config.cmid,
                    message: turn.message,
                    action: turn.action,
                    clientid: turn.clientid,
                },
            }])[0];

            pending = null;
            await appendMessage(false, result.assistant);
            updateCount(result.interactioncount, result.maxinteractions);
            root.querySelector('[data-region="input"]').value = '';

            if (result.completed) {
                markCompleted();
            }
        } catch (error) {
            const retryable = error && error.errorcode === 'aiunavailable';

            if (retryable) {
                pending = turn;
                showError(config.retryableerror, true);
            } else {
                pending = null;

                if (provisional) {
                    provisional.remove();
                }

                showError(error && error.message ? error.message : config.retryableerror, false);
            }
        } finally {
            setBusy(false);
        }
    };

    const startTurn = (action) => {
        if (busy || pending) {
            return;
        }

        const input = root.querySelector('[data-region="input"]');
        let message = input.value.trim();
        let display = message;

        if (action === 'hint') {
            message = '';
            display = config.hintlabel;
        } else if (action === 'final') {
            message = '';
            display = config.finallabel;
        }

        if (action === 'message' && !message) {
            input.focus();
            return;
        }

        requestTurn({
            action,
            message,
            display,
            clientid: makeClientId(),
        }, true);
    };

    const endConversation = async () => {
        if (busy) {
            return;
        }

        clearError();
        setBusy(true);

        try {
            await Ajax.call([{
                methodname: 'mod_socratic_end_conversation',
                args: {
                    cmid: config.cmid,
                },
            }])[0];

            pending = null;
            markCompleted();
        } catch (error) {
            Notification.exception(error);
        } finally {
            setBusy(false);
        }
    };

    const bind = () => {
        root.querySelector('[data-action="send"]').addEventListener('click', () => startTurn('message'));

        const hint = root.querySelector('[data-action="hint"]');
        if (hint) {
            hint.addEventListener('click', () => startTurn('hint'));
        }

        const finalAnswer = root.querySelector('[data-action="final"]');
        if (finalAnswer) {
            finalAnswer.addEventListener('click', () => startTurn('final'));
        }

        root.querySelector('[data-action="end"]').addEventListener('click', endConversation);
        root.querySelector('[data-action="retry"]').addEventListener('click', () => {
            if (pending) {
                requestTurn(pending, false);
            }
        });

        root.querySelector('[data-region="input"]').addEventListener('keydown', (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                event.preventDefault();
                startTurn('message');
            }
        });
    };

    const init = (options) => {
        config = options;
        root = document.querySelector(`[data-region="socratic-root"][data-cmid="${config.cmid}"]`);

        if (!root) {
            return;
        }

        pending = config.pending || null;
        bind();
        setBusy(false);

        if (pending) {
            showError(config.retryableerror, true);
        }

        scrollMessages();
    };

    return {
        init,
    };
});
