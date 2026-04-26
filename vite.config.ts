import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'path'

export default createAppConfig(
	{
		deviceList: resolve(join('src', 'views', 'DeviceList', 'index.ts')),
		deviceEditor: resolve(join('src', 'views', 'DeviceEditor', 'index.ts')),
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
