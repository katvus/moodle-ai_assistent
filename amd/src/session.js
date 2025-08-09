import {call} from 'core/ajax';

export const getSession = (
    isNew
) => call([{
    methodname: 'block_aiassistant_get_session',
    args: {
        isNew
    },
}])[0];