<template>
  <div class="cie10-search-dropdown" ref="dropdownRef">
    <div class="relative">
      <!-- Input de búsqueda -->
      <div class="relative">
        <input
          ref="searchInput"
          v-model="searchQuery"
          type="text"
          :placeholder="placeholder"
          class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
          :class="{
            'border-red-500': hasError,
            'border-green-500': selectedItem && !hasError
          }"
          @focus="handleFocus"
          @blur="handleBlur"
          @keydown="handleKeyDown"
          @input="handleInput"
        />
        
        <!-- Icono de búsqueda/limpiar -->
        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
          <button
            v-if="selectedItem"
            @click="clearSelection"
            class="text-gray-400 hover:text-gray-600 transition-colors"
            type="button"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
          <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>
      </div>

      <!-- Dropdown de resultados -->
      <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-1"
      >
        <div
          v-if="showDropdown && (filteredResults.length > 0 || loading || searchQuery.length > 0)"
          class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
        >
          <!-- Loading state -->
          <div v-if="loading" class="p-4 text-center text-gray-500">
            <div class="inline-flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Buscando...
            </div>
          </div>

          <!-- No results -->
          <div v-else-if="searchQuery.length > 0 && filteredResults.length === 0" class="p-4 text-center text-gray-500">
            No se encontraron códigos CIE-10 que coincidan con "{{ searchQuery }}"
          </div>

          <!-- Resultados -->
          <div v-else-if="filteredResults.length > 0">
            <div
              v-for="(result, index) in filteredResults"
              :key="result.item.code"
              @click="selectItem(result.item)"
              @mouseenter="highlightedIndex = index"
              class="px-4 py-3 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors"
              :class="{
                'bg-blue-50 text-blue-900': highlightedIndex === index,
                'hover:bg-gray-50': highlightedIndex !== index
              }"
            >
              <div class="flex items-start space-x-3">
                <!-- Indicador de nivel -->
                <div class="flex-shrink-0 mt-1">
                  <span 
                    class="inline-block w-2 h-2 rounded-full"
                    :class="getLevelColor(result.item.level)"
                  ></span>
                </div>
                
                <div class="flex-1 min-w-0">
                  <!-- Código CIE-10 -->
                  <div class="flex items-center space-x-2">
                    <span class="font-mono text-sm font-medium text-blue-600">
                      {{ result.item.code }}
                    </span>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                      Nivel {{ result.item.level }}
                    </span>
                  </div>
                  
                  <!-- Descripción con resaltado -->
                  <div class="mt-1 text-sm text-gray-700">
                    <span v-html="highlightMatch(result.item.description, searchQuery)"></span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Hint de navegación -->
          <div v-if="filteredResults.length > 0" class="px-4 py-2 bg-gray-50 border-t text-xs text-gray-500">
            Usa ↑↓ para navegar, Enter para seleccionar, Esc para cerrar
          </div>
        </div>
      </transition>
    </div>

    <!-- Valor seleccionado (mostrar debajo del input si está seleccionado) -->
    <div v-if="selectedItem && !showDropdown" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <div class="flex items-center space-x-2 mb-1">
            <span class="font-mono text-sm font-medium text-blue-700">
              {{ selectedItem.code }}
            </span>
            <span class="text-xs text-blue-600 bg-blue-100 px-2 py-1 rounded">
              Nivel {{ selectedItem.level }}
            </span>
          </div>
          <p class="text-sm text-blue-700">{{ selectedItem.description }}</p>
        </div>
        <button
          @click="clearSelection"
          class="ml-2 text-blue-400 hover:text-blue-600 transition-colors"
          type="button"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Error message -->
    <div v-if="hasError && errorMessage" class="mt-1 text-sm text-red-600">
      {{ errorMessage }}
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import Fuse from 'fuse.js'

export default {
  name: 'CIE10SearchDropdown',
  props: {
    modelValue: {
      type: [Object, String],
      default: null
    },
    placeholder: {
      type: String,
      default: 'Buscar código CIE-10...'
    },
    required: {
      type: Boolean,
      default: false
    },
    errorMessage: {
      type: String,
      default: ''
    },
    maxResults: {
      type: Number,
      default: 50
    },
    minSearchLength: {
      type: Number,
      default: 2
    }
  },
  emits: ['update:modelValue', 'select', 'clear'],
  setup(props, { emit }) {
    // Referencias reactivas
    const searchQuery = ref('')
    const selectedItem = ref(null)
    const showDropdown = ref(false)
    const loading = ref(false)
    const highlightedIndex = ref(-1)
    const dropdownRef = ref(null)
    const searchInput = ref(null)
    
    // Datos CIE-10 y Fuse instance
    const cie10Data = ref([])
    const fuse = ref(null)
    
    // Computed properties
    const hasError = computed(() => {
      return props.required && !selectedItem.value && props.errorMessage
    })
    
    const filteredResults = computed(() => {
      if (!fuse.value || searchQuery.value.length < props.minSearchLength) {
        return []
      }
      
      const results = fuse.value.search(searchQuery.value)
      return results.slice(0, props.maxResults)
    })
    
    // Cargar datos CIE-10
    const loadCIE10Data = async () => {
      try {
        loading.value = true
        // Ajusta esta ruta según donde tengas el archivo JSON
        const response = await fetch('/data/cie10.json')
        const data = await response.json()
        
        cie10Data.value = data
        
        // Configurar Fuse.js para búsqueda optimizada
        fuse.value = new Fuse(data, {
          keys: [
            {
              name: 'code',
              weight: 0.4
            },
            {
              name: 'description',
              weight: 0.6
            }
          ],
          threshold: 0.3, // Qué tan estricta es la búsqueda (0 = exacta, 1 = muy flexible)
          distance: 100,   // Qué tan lejos pueden estar los caracteres
          includeScore: true,
          includeMatches: true,
          minMatchCharLength: 2,
          shouldSort: true,
          findAllMatches: false,
          useExtendedSearch: false
        })
      } catch (error) {
        console.error('Error cargando datos CIE-10:', error)
      } finally {
        loading.value = false
      }
    }
    
    // Métodos para manejar interacciones
    const handleFocus = () => {
      showDropdown.value = true
      if (selectedItem.value) {
        searchQuery.value = selectedItem.value.code + ' - ' + selectedItem.value.description
      }
    }
    
    const handleBlur = () => {
      // Delay para permitir que el click en una opción funcione
      setTimeout(() => {
        showDropdown.value = false
        if (selectedItem.value) {
          searchQuery.value = selectedItem.value.code
        } else {
          searchQuery.value = ''
        }
        highlightedIndex.value = -1
      }, 200)
    }
    
    const handleInput = (event) => {
      const value = event.target.value
      searchQuery.value = value
      
      // Si el usuario está escribiendo, limpiar la selección actual
      if (selectedItem.value && value !== selectedItem.value.code) {
        clearSelection()
      }
      
      showDropdown.value = value.length >= props.minSearchLength
      highlightedIndex.value = -1
    }
    
    const handleKeyDown = (event) => {
      if (!showDropdown.value) return
      
      switch (event.key) {
        case 'ArrowDown':
          event.preventDefault()
          highlightedIndex.value = Math.min(
            highlightedIndex.value + 1,
            filteredResults.value.length - 1
          )
          break
          
        case 'ArrowUp':
          event.preventDefault()
          highlightedIndex.value = Math.max(highlightedIndex.value - 1, -1)
          break
          
        case 'Enter':
          event.preventDefault()
          if (highlightedIndex.value >= 0 && filteredResults.value[highlightedIndex.value]) {
            selectItem(filteredResults.value[highlightedIndex.value].item)
          }
          break
          
        case 'Escape':
          event.preventDefault()
          showDropdown.value = false
          searchInput.value?.blur()
          break
      }
    }
    
    const selectItem = (item) => {
      selectedItem.value = item
      searchQuery.value = item.code
      showDropdown.value = false
      highlightedIndex.value = -1
      
      // Emitir eventos
      emit('update:modelValue', item)
      emit('select', item)
      
      nextTick(() => {
        searchInput.value?.blur()
      })
    }
    
    const clearSelection = () => {
      selectedItem.value = null
      searchQuery.value = ''
      showDropdown.value = false
      highlightedIndex.value = -1
      
      emit('update:modelValue', null)
      emit('clear')
      
      nextTick(() => {
        searchInput.value?.focus()
      })
    }
    
    // Utilidades de visualización
    const getLevelColor = (level) => {
      const colors = {
        0: 'bg-purple-500',
        1: 'bg-blue-500',
        2: 'bg-green-500',
        3: 'bg-yellow-500',
        4: 'bg-orange-500',
        5: 'bg-red-500'
      }
      return colors[level] || 'bg-gray-500'
    }
    
    const highlightMatch = (text, query) => {
      if (!query || query.length < 2) return text
      
      const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
      return text.replace(regex, '<mark class="bg-yellow-200 font-medium">$1</mark>')
    }
    
    // Click outside handler
    const handleClickOutside = (event) => {
      if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showDropdown.value = false
      }
    }
    
    // Watcher para modelValue externo
    watch(() => props.modelValue, (newValue) => {
      if (newValue && typeof newValue === 'object') {
        selectedItem.value = newValue
        searchQuery.value = newValue.code
      } else if (typeof newValue === 'string') {
        // Si se pasa un código, buscar el objeto completo
        const found = cie10Data.value.find(item => item.code === newValue)
        if (found) {
          selectedItem.value = found
          searchQuery.value = found.code
        }
      } else {
        selectedItem.value = null
        searchQuery.value = ''
      }
    }, { immediate: true })
    
    // Lifecycle hooks
    onMounted(() => {
      loadCIE10Data()
      document.addEventListener('click', handleClickOutside)
    })
    
    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })
    
    return {
      // Referencias
      searchQuery,
      selectedItem,
      showDropdown,
      loading,
      highlightedIndex,
      dropdownRef,
      searchInput,
      
      // Computed
      hasError,
      filteredResults,
      
      // Métodos
      handleFocus,
      handleBlur,
      handleInput,
      handleKeyDown,
      selectItem,
      clearSelection,
      getLevelColor,
      highlightMatch
    }
  }
}
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
.cie10-search-dropdown {
  position: relative;
}

mark {
  padding: 0;
  background-color: #fef08a;
  border-radius: 2px;
}

/* Scrollbar personalizado para el dropdown */
.cie10-search-dropdown ::-webkit-scrollbar {
  width: 6px;
}

.cie10-search-dropdown ::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.cie10-search-dropdown ::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.cie10-search-dropdown ::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>