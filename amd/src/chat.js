import {renderForPromise, runTemplateJS} from 'core/templates';
import {requestForAssistant} from './request';
import {getSession} from './session';
import {loadHistory} from './history';

export const init = (instanceid) => {
    // eslint-disable-next-line no-console
    console.log("⚡ INIT WORK");
    const parent = document.querySelector(`[class="footer"][data-instance-id="${instanceid}"]`);
    const submitButton = parent.querySelector('[data-action="submit"]');
    const textarea = parent.querySelector('[data-region="input"]');
    const chat = document.querySelector(`[data-role="chat"][data-instance-id="${instanceid}"]`);
    const newChatButton = document.querySelector(`[data-action="new-chat"][data-instance-id="${instanceid}"]`);

    let session = null;
    const promise = getSession();
    promise.done(function(sessionInfo) {
        session = sessionInfo.sessionid;
        // eslint-disable-next-line no-console
        console.log("currentSession:", sessionInfo);
        if (sessionInfo.isNew == false) {
            const promiseHistory = loadHistory(sessionInfo.sessionid);
            promiseHistory.done(function(history) {
                addDialogue(history, chat);
            }).fail(function(error) {
                // eslint-disable-next-line no-console
                console.log("error", error);
            });
        }
    }).fail(function(error) {
        // eslint-disable-next-line no-console
        console.log("error", error);
    });

    submitButton.addEventListener('click', () => {
        if (textarea) {
            const text = textarea.value.trim();
            if (text !== '') {
                const date = new Date();
                const time = Math.floor(date.getTime() / 1000);
                addMessage('user', text, date, chat);
                textarea.value = '';
                const promise = requestForAssistant(text, time, session);
                promise.done(function(response) {
                    if (response.status == "success") {
                        // eslint-disable-next-line no-console
                        console.log("answer:", response.answer);
                        addMessage('assistant', response.answer, new Date(response.answertime * 1000), chat);
                    } else {
                        // eslint-disable-next-line no-console
                        console.log("error:", response.message);
                    }
                }).fail(function(fail) {
                    // eslint-disable-next-line no-console
                    console.log("error", fail);
                });
            } else {
                // Message for user: The message shouldn't be empty
            }
        } else {
            // eslint-disable-next-line no-console
            console.error('Textarea not found!');
        }
    });

    newChatButton.addEventListener('click', () => {
        chat.textContent = '';
        const promise = getSession(true);
        promise.done(function(sessionInfo) {
            session = sessionInfo.sessionid;
            // eslint-disable-next-line no-console
            console.log("currentSession:", sessionInfo);
        }).fail(function(error) {
            // eslint-disable-next-line no-console
            console.log("error", error);
        });
    });
};
/**
 * Add message
 * @param {'user' | 'assistant'} role
 * @param {string} text
 * @param {Data} time
 * @param {*} chat a message is added here
 */
async function addMessage(role, text, time, chat) {
    const {html, js} = await renderForPromise('block_aiassistant/messages', {
            role: role,
            text: text,
            time: time.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', hourCycle: 'h24'})
        });
    chat.insertAdjacentHTML('beforeend', html);
    chat.scrollTop = chat.scrollHeight;
    if (js) {
        runTemplateJS(js);
    }
}

/**
 * Add message
 * @param {Array.<[string,string,number]>} messages
 * @param {*} chat a message is added here
 */
async function addDialogue(messages, chat) {
    for (const message of messages) {
        addMessage(message.role, message.text, new Date(message.time * 1000), chat);
    }
}