import {call} from 'core/ajax';

export const loadHistory = (
    sessionid,
) => call([{
    methodname: 'block_aiassistant_load_history',
    args: {
        sessionid
    },
}])[0];