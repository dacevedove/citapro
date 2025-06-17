<template>
  <div class="cie10-search-dropdown" ref="dropdownRef">
    <div class="search-container">
      <!-- Input de búsqueda -->
      <div class="input-wrapper">
        <input
          ref="searchInput"
          v-model="searchQuery"
          type="text"
          :placeholder="placeholder"
          class="form-control search-input"
          :class="{
            'error': hasError,
            'success': selectedItem && !hasError
          }"
          @focus="handleFocus"
          @blur="handleBlur"
          @keydown="handleKeyDown"
          @input="handleInput"
        />
        
        <!-- Icono de búsqueda/limpiar -->
        <div class="input-icon">
          <button
            v-if="selectedItem"
            @click="clearSelection"
            class="clear-btn"
            type="button"
            title="Limpiar selección"
          >
            <i class="fas fa-times"></i>
          </button>
          <i v-else class="fas fa-search search-icon"></i>
        </div>
      </div>

      <!-- Dropdown de resultados -->
      <transition name="dropdown-fade">
        <div
          v-if="showDropdown && (filteredResults.length > 0 || loading || searchQuery.length > 0)"
          class="dropdown-menu"
        >
          <!-- Loading state -->
          <div v-if="loading" class="dropdown-loading">
            <div class="loading-content">
              <div class="spinner"></div>
              <span>Buscando códigos CIE-10...</span>
            </div>
          </div>

          <!-- No results -->
          <div v-else-if="searchQuery.length > 0 && filteredResults.length === 0" class="dropdown-empty">
            <i class="fas fa-search"></i>
            <p>No se encontraron códigos que coincidan con "<strong>{{ searchQuery }}</strong>"</p>
          </div>

          <!-- Resultados -->
          <div v-else-if="filteredResults.length > 0" class="dropdown-results">
            <div
              v-for="(result, index) in filteredResults"
              :key="result.item.code"
              @click="selectItem(result.item)"
              @mouseenter="highlightedIndex = index"
              class="dropdown-item"
              :class="{
                'highlighted': highlightedIndex === index
              }"
            >
              <div class="item-content">
                <!-- Indicador de nivel -->
                <div class="level-indicator">
                  <span 
                    class="level-dot"
                    :class="getLevelColorClass(result.item.level)"
                  ></span>
                </div>
                
                <div class="item-details">
                  <!-- Código CIE-10 -->
                  <div class="item-header">
                    <span class="cie-code">
                      {{ result.item.code }}
                    </span>
                    <span class="level-badge">
                      Nivel {{ result.item.level }}
                    </span>
                  </div>
                  
                  <!-- Descripción con resaltado -->
                  <div class="item-description">
                    <span v-html="highlightMatch(result.item.description, searchQuery)"></span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Hint de navegación -->
          <div v-if="filteredResults.length > 0" class="dropdown-hint">
            <small>
              <i class="fas fa-keyboard"></i>
              Usa ↑↓ para navegar, Enter para seleccionar, Esc para cerrar
            </small>
          </div>
        </div>
      </transition>
    </div>

    <!-- Valor seleccionado -->
    <div v-if="selectedItem && !showDropdown" class="selected-item">
      <div class="selected-content">
        <div class="selected-main">
          <div class="selected-header">
            <span class="selected-code">{{ selectedItem.code }}</span>
            <span class="selected-level">Nivel {{ selectedItem.level }}</span>
          </div>
          <p class="selected-description">{{ selectedItem.description }}</p>
        </div>
        <button
          @click="clearSelection"
          class="selected-clear"
          type="button"
          title="Limpiar selección"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>

    <!-- Error message -->
    <div v-if="hasError && errorMessage" class="error-message">
      <i class="fas fa-exclamation-triangle"></i>
      {{ errorMessage }}
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'

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
    
    // Datos CIE-10
    const cie10Data = ref([])
    
    // Computed properties
    const hasError = computed(() => {
      return props.required && !selectedItem.value && props.errorMessage
    })
    
    const filteredResults = computed(() => {
      if (searchQuery.value.length < props.minSearchLength) {
        return []
      }
      
      const query = searchQuery.value.toLowerCase()
      const results = cie10Data.value
        .filter(item => 
          item.code.toLowerCase().includes(query) || 
          item.description.toLowerCase().includes(query)
        )
        .map(item => ({ item, score: 1 }))
        .slice(0, props.maxResults)
      
      return results
    })
    
    // Cargar datos CIE-10 (simulados para demo)
    const loadCIE10Data = async () => {
      try {
        loading.value = true
        
        // Datos simulados - en producción cargarías desde un archivo JSON o API
        const simulatedData = [
          { code: 'A00', description: 'Cólera', level: 1 },
          { code: 'A00.0', description: 'Cólera debida a Vibrio cholerae 01, biotipo cholerae', level: 2 },
          { code: 'A00.1', description: 'Cólera debida a Vibrio cholerae 01, biotipo El Tor', level: 2 },
          { code: 'A00.9', description: 'Cólera, no especificado', level: 2 },
          { code: 'A01', description: 'Fiebres tifoidea y paratifoidea', level: 1 },
          { code: 'A01.0', description: 'Fiebre tifoidea', level: 2 },
          { code: 'A01.1', description: 'Fiebre paratifoidea A', level: 2 },
          { code: 'A01.2', description: 'Fiebre paratifoidea B', level: 2 },
          { code: 'A01.3', description: 'Fiebre paratifoidea C', level: 2 },
          { code: 'A01.4', description: 'Fiebre paratifoidea, no especificada', level: 2 },
          { code: 'B00', description: 'Infecciones herpéticas', level: 1 },
          { code: 'B00.0', description: 'Eczema herpético', level: 2 },
          { code: 'B00.1', description: 'Dermatitis vesicular debida a virus del herpes', level: 2 },
          { code: 'B00.2', description: 'Gingivoestomatitis y faringoamigdalitis herpéticas', level: 2 },
          { code: 'C00', description: 'Tumor maligno del labio', level: 1 },
          { code: 'C00.0', description: 'Tumor maligno del labio superior, cara externa', level: 2 },
          { code: 'C00.1', description: 'Tumor maligno del labio inferior, cara externa', level: 2 },
          { code: 'D50', description: 'Anemia por deficiencia de hierro', level: 1 },
          { code: 'D50.0', description: 'Anemia por deficiencia de hierro secundaria a pérdida de sangre (crónica)', level: 2 },
          { code: 'D50.1', description: 'Anemia sideropénica disbásica', level: 2 },
          { code: 'E10', description: 'Diabetes mellitus tipo 1', level: 1 },
          { code: 'E10.0', description: 'Diabetes mellitus tipo 1 con coma', level: 2 },
          { code: 'E10.1', description: 'Diabetes mellitus tipo 1 con cetoacidosis', level: 2 },
          { code: 'E11', description: 'Diabetes mellitus tipo 2', level: 1 },
          { code: 'E11.0', description: 'Diabetes mellitus tipo 2 con coma', level: 2 },
          { code: 'F00', description: 'Demencia en la enfermedad de Alzheimer', level: 1 },
          { code: 'G00', description: 'Meningitis bacteriana, no clasificada en otra parte', level: 1 },
          { code: 'H00', description: 'Orzuelo y chalazión', level: 1 },
          { code: 'I00', description: 'Fiebre reumática sin mención de complicación cardíaca', level: 1 },
          { code: 'J00', description: 'Rinofaringitis aguda [resfriado común]', level: 1 },
          { code: 'K00', description: 'Trastornos del desarrollo y de la erupción de los dientes', level: 1 },
          { code: 'L00', description: 'Síndrome de la piel escaldada estafilocócica', level: 1 },
          { code: 'M00', description: 'Artritis piógena', level: 1 },
          { code: 'N00', description: 'Síndrome nefrítico agudo', level: 1 },
          { code: 'O00', description: 'Embarazo ectópico', level: 1 },
          { code: 'P00', description: 'Feto y recién nacido afectados por trastornos maternos no relacionados necesariamente con el embarazo actual', level: 1 },
          { code: 'Q00', description: 'Anencefalia y malformaciones similares', level: 1 },
          { code: 'R00', description: 'Anormalidades del latido cardíaco', level: 1 },
          { code: 'S00', description: 'Traumatismo superficial de la cabeza', level: 1 },
          { code: 'T00', description: 'Traumatismos superficiales que afectan múltiples regiones del cuerpo', level: 1 },
          { code: 'V00', description: 'Peatón lesionado en accidente de transporte', level: 1 },
          { code: 'W00', description: 'Caída en el mismo nivel por hielo y nieve', level: 1 },
          { code: 'X00', description: 'Exposición a fuego no controlado en edificio u otra construcción', level: 1 },
          { code: 'Y00', description: 'Envenenamiento por analgésicos no narcóticos, antipiréticos y antirreumáticos, intención no determinada', level: 1 },
          { code: 'Z00', description: 'Examen general e investigación de personas sin quejas o sin diagnóstico informado', level: 1 }
        ]
        
        cie10Data.value = simulatedData
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
    const getLevelColorClass = (level) => {
      const colors = {
        0: 'level-0',
        1: 'level-1',
        2: 'level-2',
        3: 'level-3',
        4: 'level-4',
        5: 'level-5'
      }
      return colors[level] || 'level-default'
    }
    
    const highlightMatch = (text, query) => {
      if (!query || query.length < 2) return text
      
      const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi')
      return text.replace(regex, '<mark>$1</mark>')
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
      searchQuery,
      selectedItem,
      showDropdown,
      loading,
      highlightedIndex,
      dropdownRef,
      searchInput,
      hasError,
      filteredResults,
      handleFocus,
      handleBlur,
      handleInput,
      handleKeyDown,
      selectItem,
      clearSelection,
      getLevelColorClass,
      highlightMatch
    }
  }
}
</script>

<style scoped>
/* Variables CSS para mantener consistencia */
:root {
  --primary-color: #007bff;
  --secondary-color: #6c757d;
  --success-color: #28a745;
  --danger-color: #dc3545;
  --warning-color: #ffc107;
  --info-color: #17a2b8;
  --light-color: #f8f9fa;
  --dark-color: #343a40;
  --border-color: #ced4da;
  --hover-color: #e9ecef;
}

.cie10-search-dropdown {
  position: relative;
  width: 100%;
}

/* Input Container */
.search-container {
  position: relative;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-input {
  width: 100%;
  padding: 10px 40px 10px 12px;
  border: 1px solid var(--border-color);
  border-radius: 4px;
  font-size: 16px;
  transition: all 0.2s ease;
  background-color: white;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.search-input.error {
  border-color: var(--danger-color);
}

.search-input.error:focus {
  border-color: var(--danger-color);
  box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.25);
}

.search-input.success {
  border-color: var(--success-color);
}

.input-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.search-icon {
  color: var(--secondary-color);
  font-size: 14px;
}

.clear-btn {
  background: none;
  border: none;
  color: var(--secondary-color);
  cursor: pointer;
  padding: 4px;
  border-radius: 50%;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
}

.clear-btn:hover {
  color: var(--danger-color);
  background-color: rgba(220, 53, 69, 0.1);
}

/* Dropdown Menu */
.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 1000;
  background-color: white;
  border: 1px solid var(--border-color);
  border-radius: 4px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-top: 2px;
  max-height: 300px;
  overflow-y: auto;
}

/* Loading State */
.dropdown-loading {
  padding: 20px;
  text-align: center;
}

.loading-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: var(--secondary-color);
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid var(--light-color);
  border-top: 2px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Empty State */
.dropdown-empty {
  padding: 20px;
  text-align: center;
  color: var(--secondary-color);
}

.dropdown-empty i {
  font-size: 24px;
  margin-bottom: 10px;
  opacity: 0.5;
}

.dropdown-empty p {
  margin: 0;
}

/* Results */
.dropdown-results {
  max-height: 240px;
  overflow-y: auto;
}

.dropdown-item {
  padding: 12px 15px;
  cursor: pointer;
  border-bottom: 1px solid #f8f9fa;
  transition: all 0.2s ease;
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover,
.dropdown-item.highlighted {
  background-color: var(--light-color);
}

.item-content {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.level-indicator {
  flex-shrink: 0;
  margin-top: 2px;
}

.level-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.level-0 { background-color: #6f42c1; }
.level-1 { background-color: var(--primary-color); }
.level-2 { background-color: var(--success-color); }
.level-3 { background-color: var(--warning-color); }
.level-4 { background-color: #fd7e14; }
.level-5 { background-color: var(--danger-color); }
.level-default { background-color: var(--secondary-color); }

.item-details {
  flex: 1;
  min-width: 0;
}

.item-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.cie-code {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: var(--primary-color);
  font-size: 14px;
}

.level-badge {
  background-color: var(--light-color);
  color: var(--secondary-color);
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 11px;
  font-weight: 500;
}

.item-description {
  color: var(--dark-color);
  font-size: 14px;
  line-height: 1.4;
}

/* Dropdown Hint */
.dropdown-hint {
  background-color: var(--light-color);
  border-top: 1px solid var(--border-color);
  padding: 8px 15px;
  color: var(--secondary-color);
}

.dropdown-hint i {
  margin-right: 5px;
}

/* Selected Item */
.selected-item {
  margin-top: 10px;
  background-color: #e3f2fd;
  border: 1px solid #2196f3;
  border-radius: 4px;
  padding: 12px;
}

.selected-content {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.selected-main {
  flex: 1;
  min-width: 0;
}

.selected-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.selected-code {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  color: #1976d2;
  font-size: 14px;
}

.selected-level {
  background-color: #bbdefb;
  color: #1976d2;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 11px;
  font-weight: 500;
}

.selected-description {
  color: #1976d2;
  font-size: 14px;
  margin: 0;
  line-height: 1.4;
}

.selected-clear {
  background: none;
  border: none;
  color: #1976d2;
  cursor: pointer;
  padding: 4px;
  border-radius: 50%;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.selected-clear:hover {
  background-color: rgba(25, 118, 210, 0.1);
}

/* Error Message */
.error-message {
  margin-top: 5px;
  color: var(--danger-color);
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 5px;
}

/* Highlight Text */
mark {
  background-color: #fff3cd;
  color: #856404;
  padding: 0 2px;
  border-radius: 2px;
  font-weight: 500;
}

/* Transitions */
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: all 0.2s ease;
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.dropdown-fade-enter-to,
.dropdown-fade-leave-from {
  opacity: 1;
  transform: translateY(0);
}

/* Scrollbar personalizado */
.dropdown-menu::-webkit-scrollbar {
  width: 6px;
}

.dropdown-menu::-webkit-scrollbar-track {
  background: var(--light-color);
  border-radius: 3px;
}

.dropdown-menu::-webkit-scrollbar-thumb {
  background: var(--border-color);
  border-radius: 3px;
}

.dropdown-menu::-webkit-scrollbar-thumb:hover {
  background: var(--secondary-color);
}

/* Responsive */
@media (max-width: 768px) {
  .search-input {
    font-size: 16px; /* Evita zoom en iOS */
    padding: 12px 40px 12px 15px;
  }
  
  .dropdown-item {
    padding: 15px;
  }
  
  .item-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  
  .selected-content {
    flex-direction: column;
    gap: 10px;
  }
  
  .selected-clear {
    align-self: flex-end;
  }
}

@media (max-width: 576px) {
  .dropdown-menu {
    max-height: 250px;
  }
  
  .item-content {
    flex-direction: column;
    gap: 8px;
  }
  
  .level-indicator {
    order: 2;
  }
  
  .item-details {
    order: 1;
  }
}
</style>