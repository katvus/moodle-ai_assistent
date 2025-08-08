import {call} from 'core/ajax';

export const requestForAssistant = (
    question,
    questiontime,
    sessionid
) => call([{
    methodname: 'block_aiassistant_request_assistant',
    args: {
        question,
        questiontime,
        sessionid
    },
}])[0];