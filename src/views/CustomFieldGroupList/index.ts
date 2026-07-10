import { createApp } from 'vue'
import CustomFieldGroupList from './CustomFieldGroupList.vue'

const el = document.getElementById('sfxonitam')
const customFieldGroups = JSON.parse(el?.dataset.customFieldGroups ?? '[]')

const app = createApp(CustomFieldGroupList, { customFieldGroups })
app.mount('#sfxonitam')
