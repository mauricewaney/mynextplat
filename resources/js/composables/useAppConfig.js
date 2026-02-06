export function useAppConfig() {
    const appName = import.meta.env.VITE_APP_NAME || 'MyNextPlat'

    return { appName }
}
