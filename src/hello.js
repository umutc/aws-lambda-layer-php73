exports.handler = async (event) => {
    const response = {
        msg: `hello from Node.js ${process.version}`,
        eventData: event,
        data: typeof event === 'string' ? JSON.parse(event) : event
    };
    
    return response;
};
