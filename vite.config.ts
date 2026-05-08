import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'path'

export default createAppConfig(
    {
        deviceList: resolve(join('src', 'views', 'DeviceList', 'index.ts')),
        deviceEditor: resolve(join('src', 'views', 'DeviceEditor', 'index.ts')),
        deviceStatusList: resolve(join('src', 'views', 'DeviceStatusList', 'index.ts')),
        deviceStatusEditor: resolve(join('src', 'views', 'DeviceStatusEditor', 'index.ts')),
        deviceTypeList: resolve(join('src', 'views', 'DeviceTypeList', 'index.ts')),
        deviceTypeEditor: resolve(join('src', 'views', 'DeviceTypeEditor', 'index.ts')),
        itamUserList: resolve(join('src', 'views', 'ItamUserList', 'index.ts')),
        itamUserEditor: resolve(join('src', 'views', 'ItamUserEditor', 'index.ts')),
        locationList: resolve(join('src', 'views', 'LocationList', 'index.ts')),
        locationEditor: resolve(join('src', 'views', 'LocationEditor', 'index.ts')),
        manufacturerList: resolve(join('src', 'views', 'ManufacturerList', 'index.ts')),
        manufacturerEditor: resolve(join('src', 'views', 'ManufacturerEditor', 'index.ts')),
        positionList: resolve(join('src', 'views', 'PositionList', 'index.ts')),
        positionEditor: resolve(join('src', 'views', 'PositionEditor', 'index.ts')),
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
