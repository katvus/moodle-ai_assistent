import {renderForPromise, runTemplateJS} from 'core/templates';

export const init = () => {
    // eslint-disable-next-line no-console
    console.log("⚡ INIT WORK");
    const submitButton = document.querySelector('[data-action="submit"]');
    const textarea = document.querySelector('[data-region="input"]');
    // require(['core/templates'], function(templates) {
    //     // eslint-disable-next-line no-console
    //     console.log('Available methods:', Object.keys(templates));
    // });

    // eslint-disable-next-line no-console
    console.log({submitButton, textarea});
    submitButton.addEventListener('click', () => {
        // eslint-disable-next-line no-console
        console.log('Click');
        if (textarea) {
            const text = textarea.value.trim();
            if (text !== '') {
                // eslint-disable-next-line no-console
                console.log(text);
                addMessage(text, 'user');
                textarea.value = '';
                const answer = 'Hello world!';// Function, which return text
                addMessage(answer, 'assistant');
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
 */
async function addMessage(text, role) {
    // Очистка текста
    // const safeText = await sanitize(text);
    // Рендеринг шаблона
    const {html, js} = await renderForPromise('block_aiassistant/messages', {
            role: role,
            text: text,
            time: new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', hourCycle: 'h24'})
        });
    const chat = document.querySelector('[data-role="chat"]');
    chat.insertAdjacentHTML('beforeend', html);
    chat.scrollTop = chat.scrollHeight;
    if (js) {
        runTemplateJS(js);
    }
}