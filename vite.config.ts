import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'path'

export default createAppConfig(
    {
        deviceList: resolve(join('src', 'views', 'DeviceList', 'index.ts')),
        deviceEditor: resolve(join('src', 'views', 'DeviceEditor', 'index.ts')),
        deviceStatusList: resolve(join('src', 'views', 'DeviceStatusList', 'index.ts')),
        deviceStatusEditor: resolve(join('src', 'views', 'DeviceStatusEditor', 'index.ts')),
    },
    {
        createEmptyCSSEntryPoints: true,
        extractLicenseInformation: true,
        thirdPartyLicense: false,
        config: {
            resolve: {
                alias: {
                    '@': resolve('src'),
                },
            },
        },
    },
)
