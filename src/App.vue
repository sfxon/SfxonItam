<script setup lang="ts">
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import { mdiPlus, mdiPencil, mdiTrashCan } from '@mdi/js'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { ref } from 'vue'
import { translate as t } from '@nextcloud/l10n'

interface Item {
  id: number
  name: string
  subname: string
  active: boolean
}

const items = ref<Item[]>([
  { id: 1, name: 'Erstes Element',   subname: 'Zuletzt geändert vor 2 Min.',  active: false },
  { id: 2, name: 'Zweites Element',  subname: 'Zuletzt geändert vor 1 Std.',  active: false },
  { id: 3, name: 'Drittes Element',  subname: 'Zuletzt geändert gestern',     active: false },
  { id: 4, name: 'Viertes Element',  subname: 'Zuletzt geändert letzte Woche', active: false },
])

const selectedItem = ref<Item | null>(null)

function selectItem(item: Item) {
  items.value.forEach(i => (i.active = false))
  item.active = true
  selectedItem.value = item
}

function addItem() {
  const id = items.value.length + 1
  items.value.push({
    id,
    name: `Neues Element ${id}`,
    subname: 'Gerade erstellt',
    active: false,
  })
}

function deleteItem(item: Item) {
  items.value = items.value.filter(i => i.id !== item.id)
  if (selectedItem.value?.id === item.id) {
    selectedItem.value = null
  }
}

function renameItem(item: Item) {
  const newName = prompt('Neuer Name:', item.name)
  if (newName) item.name = newName
}
</script>

<template>
    <NcContent app-name="sfxonitam">
        <NcAppNavigation>
            <template #list>
                <NcAppNavigationNew
                :text="t('sfxonitam', 'Neues Element')"
                @click="addItem"
                >
                <template #icon>
                    <NcIconSvgWrapper :path="mdiPlus" :size="20" />
                </template>
                </NcAppNavigationNew>

                <NcAppNavigationItem
                v-for="item in items"
                :key="item.id"
                :name="item.name"
                :active="item.active"
                @click="selectItem(item)"
                >
                <template #actions>
                    <NcActionButton @click.stop="renameItem(item)">
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiPencil" :size="20" />
                    </template>
                    {{ t('sfxonitam', 'Umbenennen') }}
                    </NcActionButton>
                    <NcActionButton @click.stop="deleteItem(item)">
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiTrashCan" :size="20" />
                    </template>
                    {{ t('sfxonitam', 'Löschen') }}
                    </NcActionButton>
                </template>
                </NcAppNavigationItem>
            </template>
            </NcAppNavigation>

        <!-- Rechter Inhaltsbereich -->
        <NcAppContent :class="$style.content">
            <div v-if="selectedItem" :class="$style.detail">
                <h2>{{ selectedItem.name }}</h2>
                <p class="nc-text-muted">
                {{ selectedItem.subname }}
                </p>

                <ul :class="$style.list">
                <NcListItem
                    v-for="n in 5"
                    :key="n"
                    :name="`Unterpunkt ${n} von ${selectedItem.name}`"
                    :subname="`Details zu Unterpunkt ${n}`"
                >
                    <template #actions>
                    <NcActions>
                        <NcActionButton @click="() => {}">
                        <template #icon>
                            <NcIconSvgWrapper :path="mdiPencil" :size="20" />
                        </template>
                        {{ t('sfxonitam', 'Bearbeiten') }}
                        </NcActionButton>
                        <NcActionButton @click="() => {}">
                        <template #icon>
                            <NcIconSvgWrapper :path="mdiTrashCan" :size="20" />
                        </template>
                        {{ t('sfxonitam', 'Löschen') }}
                        </NcActionButton>
                    </NcActions>
                    </template>
                </NcListItem>
                </ul>
            </div>

            <div v-else :class="$style.empty">
                <p>{{ t('sfxonitam', 'Wähle ein Element aus der Navigation aus.') }}</p>
            </div>
        </NcAppContent>
    </NcContent>
</template>

<style module>
.content {
    display: flex;
    justify-content: center;
    margin: 16px;
}

.detail {
    padding: 16px 24px;
    max-width: 800px;
}

.list {
    margin-top: 16px;
    list-style: none;
    padding: 0;
}

.empty {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    opacity: 0.6;
}
</style>
