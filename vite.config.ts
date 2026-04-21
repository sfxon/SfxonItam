import { createAppConfig } from '@nextcloud/vite-config'
import { join, resolve } from 'path'

export default createAppConfig(
	{
		main: resolve(join('src', 'main.js')),
		deviceEditor: resolve(join('src', 'device-editor.js')),
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
