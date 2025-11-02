import {call} from 'core/ajax';

export const getSession = (
    instanceid,
    isNew
) => call([{
    methodname: 'block_aiassistant_get_session',
    args: {
        instanceid,
        isNew
    },
}])[0];