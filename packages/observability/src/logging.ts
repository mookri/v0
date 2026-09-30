export function logInfo(message: string, context?: Record<string, any>) { console.log(message, context); }
export function logError(error: Error | string, context?: Record<string, any>) { console.error(error, context); }
