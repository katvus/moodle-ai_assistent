import {renderForPromise, runTemplateJS} from 'core/templates';
import {requestForAssistant} from './request';

export const init = (instanceid) => {
    // eslint-disable-next-line no-console
    console.log("⚡ INIT WORK");
    const parent = document.querySelector(`[class="footer"][data-instance-id="${instanceid}"]`);
    const submitButton = parent.querySelector('[data-action="submit"]');
    const textarea = parent.querySelector('[data-region="input"]');
    const chat = document.querySelector(`[data-role="chat"][data-instance-id="${instanceid}"]`);
    let session = 15;

    // eslint-disable-next-line no-console
    console.log({submitButton, textarea});
    submitButton.addEventListener('click', () => {
        if (textarea) {
            const text = textarea.value.trim();
            if (text !== '') {
                const date = new Date();
                const time = Math.floor(date.getTime() / 1000);
                addMessage(text, 'user', date, chat);
                textarea.value = '';
                const promise = requestForAssistant(text, time, session);
                promise.done(function(response) {
                    if (response.status == "success") {
                        // eslint-disable-next-line no-console
                        console.log("answer:", response.answer);
                        addMessage(response.answer, 'assistant', new Date(response.answertime * 1000), chat);
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
};
/**
 * Add message
 * @param {message} text
 * @param {*user or assistant} role
 * @param {Data} time
 * @param {a message is added here} chat
 */
async function addMessage(text, role, time, chat) {
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