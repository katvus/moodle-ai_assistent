import {renderForPromise, runTemplateJS} from 'core/templates';
import {requestForAssistant, checkStatus} from './request';
import {getSession} from './session';
import {loadHistory} from './history';

export const init = (instanceid) => {
    const parent = document.querySelector(`[class="footer"][data-instance-id="${instanceid}"]`);
    const submitButton = parent.querySelector('[data-action="submit"]');
    const textarea = parent.querySelector('[data-region="input"]');
    const chat = document.querySelector(`[data-role="chat"][data-instance-id="${instanceid}"]`);
    const newChatButton = document.querySelector(`[data-action="new-chat"][data-instance-id="${instanceid}"]`);

    let session = null;
    const promise = getSession(instanceid);
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
                const savedDate = localStorage.getItem('savedDate');
                const currentDate = new Date().toDateString();
                if (!savedDate || savedDate !== currentDate) {
                    const date = new Date();
                    const time = Math.floor(date.getTime() / 1000);
                    addMessage('user', text, date, chat);
                    textarea.value = '';
                    chat.insertAdjacentHTML('beforeend',
                        `<div id="request-loading" class="loading-indicator"></div>`
                    );
                    const loadingIndicator = chat.querySelector("#request-loading");
                    sendQuestion(text, time, session)
                    .then(response => {
                        loadingIndicator.remove();
                        if (response.status === 'success') {
                            addMessage('assistant', response.answer, new Date(response.answertime * 1000), chat);
                        }
                        else if (response.status === 'request_limit') {
                            const today = new Date().toDateString();
                            localStorage.setItem('savedDate', today);
                            addMessage('assistant', 'You have send too many requests today. We are waiting for you tomorrow.',
                                new Date(), chat);
                        }
                    })
                    .catch(() => {
                        loadingIndicator.remove();
                        addMessage('assistant', 'Sorry, something went wrong. Please try again.', new Date(), chat);
                    });
                } else {
                    addMessage('assistant', 'You have send too many requests today. We are waiting for you tomorrow.',
                        new Date(), chat);
                }
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
        const promise = getSession(instanceid, true);
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
    // eslint-disable-next-line no-console
    console.log("add message", text);
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
        if (message.text !== null) {
            addMessage(message.role, message.text, new Date(message.time * 1000), chat);
        } else {
            addMessage(message.role, 'Error', new Date(message.time * 1000), chat);
        }
    }
}

/**
 * Send question to ai assistant
 * @param {string} text
 * @param {Date} time
 * @param {number} session
 * @returns {function} check the availability of the request result
 * @throws {Error}
 */
async function sendQuestion(text, time, session) {
    try {
        const response = await requestForAssistant(text, time, session);
        if (response.status == "queue") {
            // eslint-disable-next-line no-console
            console.log("id of request:", response.id);
            return await waitForResult(response.id);
        } else if (response.status == "request_limit") {
            return {status: response.status};
        } else {
            // eslint-disable-next-line no-console
            console.log("error:", response.status);
            throw Error("invalid status of answer");
        }
    } catch (error) {
        // eslint-disable-next-line no-console
        console.log("error", error);
        throw error;
    }
}

/**
 * Check the availability of the request result
 * @param {number} id
 */
async function waitForResult(id) {
    const interval = 2000;
    const maxWaitingTime = 30000;
    const startTime = Date.now();
    while (Date.now() - startTime < maxWaitingTime) {
        // eslint-disable-next-line no-console
        console.log("checkStatus start");
        const response = await checkStatus(id);
        // eslint-disable-next-line no-console
        console.log("checkStatus end", response.answer);
        if (response.status === 'completed') {
            return {
                status: 'success',
                answer: response.answer,
                answertime: response.answertime
            };
        } else if (response.status === 'failed') {
            throw new Error('Request failed');
        } else if (response.status === 'request_limit') {
            return {status: 'request_limit'};
        }
        await new Promise(resolve => setTimeout(resolve, interval));
    }
    throw new Error('Request timeout');
}