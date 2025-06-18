<template>
  <div class="diagnostic-selector-container">
    <!-- Campo de búsqueda -->
    <div class="search-section">
      <label v-if="label" class="search-label">
        <i class="fas fa-search"></i>
        {{ label }}
      </label>
      
      <div class="search-input-container">
        <div class="search-box">
          <i class="fas fa-stethoscope search-icon"></i>
          <input
            ref="searchInput"
            type="text"
            v-model="searchQuery"
            :placeholder="placeholder"
            class="search-input"
            @input="onSearchInput"
            @focus="onFocus"
            @blur="onBlur"
            @keydown="onKeyDown"
            :disabled="disabled"
          />
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="clear-button"
            type="button"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <!-- Dropdown de resultados -->
        <div
          v-if="showDropdown && (searchResults.length > 0 || (searchQuery && searchResults.length === 0) || loadingData)"
          class="search-dropdown"
          ref="dropdown"
        >
          <div v-if="loadingData" class="dropdown-loading">
            <div class="spinner-small"></div>
            <span>Cargando base de datos CIE-10...</span>
          </div>
          
          <div v-else-if="loading" class="dropdown-loading">
            <div class="spinner-small"></div>
            <span>Buscando diagnósticos...</span>
          </div>
          
          <div v-else-if="searchResults.length === 0 && searchQuery" class="dropdown-empty">
            <i class="fas fa-search"></i>
            <span>No se encontraron diagnósticos para "{{ searchQuery }}"</span>
            <div v-if="debugInfo" class="debug-info">
              <small>Debug: {{ debugInfo }}</small>
            </div>
          </div>
          
          <div v-else class="dropdown-results">
            <div
              v-for="(result, index) in searchResults"
              :key="result.code"
              class="dropdown-item"
              :class="{ 
                'dropdown-item-selected': index === selectedIndex,
                'dropdown-item-disabled': isAlreadySelected(result)
              }"
              @click="selectDiagnostic(result)"
              @mouseenter="selectedIndex = index"
            >
              <div class="diagnostic-info">
                <div class="diagnostic-header">
                  <span class="diagnostic-code">{{ result.code }}</span>
                  <span v-if="result.level > 0" class="diagnostic-level">
                    Nivel {{ result.level }}
                  </span>
                </div>
                <div class="diagnostic-description">
                  {{ result.description }}
                </div>
                <div v-if="result.matches" class="diagnostic-matches">
                  <span v-for="match in result.matches" :key="match" class="match-highlight">
                    {{ match }}
                  </span>
                </div>
              </div>
              <div v-if="isAlreadySelected(result)" class="already-selected-indicator">
                <i class="fas fa-check"></i>
                <span>Seleccionado</span>
              </div>
            </div>
          </div>
          
          <div v-if="searchResults.length > 0" class="dropdown-footer">
            <small>
              {{ searchResults.length }} resultado{{ searchResults.length !== 1 ? 's' : '' }} encontrado{{ searchResults.length !== 1 ? 's' : '' }}
            </small>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista de diagnósticos seleccionados -->
    <div v-if="selectedDiagnostics.length > 0" class="selected-section">
      <h4 class="selected-title">
        <i class="fas fa-clipboard-list"></i>
        Diagnósticos Seleccionados ({{ selectedDiagnostics.length }})
      </h4>
      
      <div class="selected-list">
        <div
          v-for="diagnostic in selectedDiagnostics"
          :key="diagnostic.code"
          class="selected-item"
        >
          <div class="selected-content">
            <div class="selected-header">
              <span class="selected-code">{{ diagnostic.code }}</span>
              <span v-if="diagnostic.level > 0" class="selected-level">
                Nivel {{ diagnostic.level }}
              </span>
            </div>
            <div class="selected-description">
              {{ diagnostic.description }}
            </div>
          </div>
          <button
            @click="removeDiagnostic(diagnostic)"
            class="remove-button"
            type="button"
            :title="`Eliminar ${diagnostic.code}`"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      
      <div class="selected-actions">
        <button
          @click="clearAllDiagnostics"
          class="btn btn-outline btn-sm"
          type="button"
        >
          <i class="fas fa-trash"></i>
          Limpiar Todo
        </button>
      </div>
    </div>

    <!-- Estado vacío -->
    <div v-else-if="showEmptyState" class="empty-state">
      <i class="fas fa-clipboard-list"></i>
      <h4>No hay diagnósticos seleccionados</h4>
      <p>Utilice el campo de búsqueda para agregar diagnósticos CIE-10</p>
    </div>
  </div>
</template>

<script>
import Fuse from 'fuse.js';

let cie10Data = [];

async function loadCie10Data() {
  try {
    console.log('🔄 Intentando cargar datos CIE-10...');
    
    // Intentar múltiples rutas
    const possiblePaths = [
      '/data/cie10.json',        // public/data/
      '../data/cie10.json',      // data/ en la raíz del proyecto
      './data/cie10.json',       // data/ relativo
      '/citas.salu.pro/data/cie10.json'  // Ruta específica de tu proyecto
    ];
    
    for (const path of possiblePaths) {
      try {
        console.log(`🔄 Probando ruta: ${path}`);
        const response = await fetch(path);
        
        if (response.ok) {
          const data = await response.json();
          console.log(`✅ Datos CIE-10 cargados desde ${path}:`, data.length, 'registros');
          return data;
        }
      } catch (error) {
        console.log(`❌ Fallo en ${path}:`, error.message);
        continue;
      }
    }
    
    throw new Error('No se encontró el archivo en ninguna ruta');
    
  } catch (error) {
    console.error('❌ Error al cargar datos CIE-10:', error);
    // En lugar de datos fallback, lanzar el error
    throw error;
  }
}

export default {
  name: 'DiagnosticSelector',
  
  props: {
    // Diagnósticos ya seleccionados (array de objetos)
    modelValue: {
      type: Array,
      default: () => []
    },
    
    // Datos CIE-10 (array de objetos) - opcional, usa datos internos por defecto
    diagnosticData: {
      type: Array,
      default: null
    },
    
    // Configuración
    label: {
      type: String,
      default: 'Diagnósticos CIE-10'
    },
    
    placeholder: {
      type: String,
      default: 'Buscar diagnósticos por código o descripción...'
    },
    
    maxResults: {
      type: Number,
      default: 10
    },
    
    maxSelections: {
      type: Number,
      default: null // Sin límite por defecto
    },
    
    disabled: {
      type: Boolean,
      default: false
    },
    
    showEmptyState: {
      type: Boolean,
      default: true
    },
    
    // Opciones de Fuse.js
    fuseOptions: {
      type: Object,
      default: () => ({})
    }
  },
  
  emits: ['update:modelValue', 'diagnostic-selected', 'diagnostic-removed', 'search'],
  
  data() {
    return {
      searchQuery: '',
      searchResults: [],
      selectedIndex: -1,
      showDropdown: false,
      loading: false,
      fuse: null,
      searchTimeout: null,
      dataLoaded: false,
      loadingData: true,
      debugInfo: '' // Para mostrar información de debug
    };
  },
  
  computed: {
    selectedDiagnostics() {
      return this.modelValue || [];
    },
    
    // Usar datos proporcionados o datos internos
    activeDiagnosticData() {
      return this.diagnosticData || cie10Data;
    },
    
    defaultFuseOptions() {
      return {
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
        threshold: 0.3,
        distance: 100,
        minMatchCharLength: 2,
        includeScore: true,
        includeMatches: true,
        ...this.fuseOptions
      };
    }
  },
  
  watch: {
    activeDiagnosticData: {
      handler(newData) {
        console.log('👀 Watching activeDiagnosticData cambió:', newData?.length || 0, 'registros');
        if (newData && newData.length > 0) {
          this.initializeFuse();
        }
      },
      immediate: true
    }
  },
  
  async mounted() {
    try {
      this.loadingData = true;
      console.log('🔄 DiagnosticSelector mounted, iniciando carga de datos...');
      
      if (!this.diagnosticData) {
        console.log('📥 Cargando datos CIE-10 internos...');
        cie10Data = await loadCie10Data();
        console.log('📋 Datos cargados exitosamente:', cie10Data.length, 'registros');
        
        // Verificar que realmente se cargaron datos
        if (!cie10Data || cie10Data.length === 0) {
          throw new Error('Los datos cargados están vacíos');
        }
      } else {
        console.log('📋 Usando datos externos proporcionados:', this.diagnosticData.length, 'registros');
      }
      
      this.initializeFuse();
      this.dataLoaded = true;
      console.log('✅ DiagnosticSelector listo para usar');
      
    } catch (error) {
      console.error('❌ Error crítico al cargar datos CIE-10:', error);
      this.debugInfo = `Error: ${error.message}`;
      // No cargar datos fallback, mantener el error visible
      this.dataLoaded = false;
    } finally {
      this.loadingData = false;
    }
    
    // Cerrar dropdown al hacer click fuera
    document.addEventListener('click', this.handleOutsideClick);
  },
  
  beforeUnmount() {
    document.removeEventListener('click', this.handleOutsideClick);
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout);
    }
  },
  
  methods: {
    initializeFuse() {
      const data = this.activeDiagnosticData;
      console.log('🔧 Inicializando Fuse.js con', data?.length || 0, 'registros');
      
      if (data && data.length > 0) {
        this.fuse = new Fuse(data, this.defaultFuseOptions);
        console.log('✅ Fuse.js inicializado correctamente');
        
        // Limpiar debug info si se inicializa correctamente
        this.debugInfo = '';
      } else {
        console.warn('⚠️ No se puede inicializar Fuse.js - datos vacíos');
        this.fuse = null;
        this.debugInfo = 'Datos CIE-10 no disponibles';
      }
    },
    
    onSearchInput() {
      console.log('🔍 Búsqueda iniciada:', this.searchQuery);
      
      // Debounce la búsqueda
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }
      
      this.searchTimeout = setTimeout(() => {
        this.performSearch();
      }, 300);
    },
    
    async performSearch() {
      console.log('🎯 Ejecutando performSearch para:', this.searchQuery);
      
      if (!this.searchQuery.trim()) {
        console.log('🔍 Búsqueda vacía, ocultando dropdown');
        this.searchResults = [];
        this.showDropdown = false;
        return;
      }
      
      // Verificar estado de los datos
      console.log('📊 Estado actual:', {
        dataLoaded: this.dataLoaded,
        fuseInitialized: !!this.fuse,
        dataLength: this.activeDiagnosticData?.length || 0,
        searchQuery: this.searchQuery
      });
      
      if (!this.dataLoaded) {
        console.log('⏳ Datos aún no cargados, mostrando loading...');
        this.loading = true;
        this.showDropdown = true;
        return;
      }
      
      if (!this.fuse) {
        console.log('🔧 Fuse no inicializado, intentando reinicializar...');
        this.initializeFuse();
        
        if (!this.fuse) {
          console.error('❌ No se pudo inicializar Fuse.js');
          this.debugInfo = 'Error: Motor de búsqueda no disponible';
          this.searchResults = [];
          this.showDropdown = true;
          return;
        }
      }
      
      this.loading = true;
      this.showDropdown = true;
      
      try {
        // Simular delay para mostrar loading
        await new Promise(resolve => setTimeout(resolve, 150));
        
        console.log('🔍 Ejecutando búsqueda con Fuse.js...');
        const fuseResults = this.fuse.search(this.searchQuery);
        console.log('📋 Resultados de búsqueda:', fuseResults.length);
        
        // Log de algunos resultados para debug
        if (fuseResults.length > 0) {
          console.log('🎯 Primeros 3 resultados:', fuseResults.slice(0, 3).map(r => ({
            code: r.item.code,
            description: r.item.description,
            score: r.score
          })));
        }
        
        // Procesar resultados de Fuse
        this.searchResults = fuseResults
          .slice(0, this.maxResults)
          .map(result => {
            const item = result.item;
            const matches = result.matches ? 
              result.matches.map(match => match.value).slice(0, 2) : [];
            
            return {
              ...item,
              score: result.score,
              matches
            };
          });
        
        console.log('✅ Resultados procesados:', this.searchResults.length);
        this.selectedIndex = -1;
        
        // Emitir evento de búsqueda
        this.$emit('search', {
          query: this.searchQuery,
          results: this.searchResults
        });
        
      } catch (error) {
        console.error('❌ Error en la búsqueda:', error);
        this.searchResults = [];
        this.debugInfo = `Error en búsqueda: ${error.message}`;
      } finally {
        this.loading = false;
      }
    },
    
    selectDiagnostic(diagnostic) {
      // Verificar si ya está seleccionado
      if (this.isAlreadySelected(diagnostic)) {
        return;
      }
      
      // Verificar límite máximo
      if (this.maxSelections && this.selectedDiagnostics.length >= this.maxSelections) {
        alert(`Solo se pueden seleccionar máximo ${this.maxSelections} diagnósticos`);
        return;
      }
      
      const newDiagnostics = [...this.selectedDiagnostics, diagnostic];
      this.$emit('update:modelValue', newDiagnostics);
      this.$emit('diagnostic-selected', diagnostic);
      
      // Limpiar búsqueda
      this.clearSearch();
    },
    
    removeDiagnostic(diagnostic) {
      const newDiagnostics = this.selectedDiagnostics.filter(
        item => item.code !== diagnostic.code
      );
      this.$emit('update:modelValue', newDiagnostics);
      this.$emit('diagnostic-removed', diagnostic);
    },
    
    clearAllDiagnostics() {
      if (confirm('¿Está seguro de que desea eliminar todos los diagnósticos seleccionados?')) {
        this.$emit('update:modelValue', []);
      }
    },
    
    isAlreadySelected(diagnostic) {
      return this.selectedDiagnostics.some(item => item.code === diagnostic.code);
    },
    
    clearSearch() {
      this.searchQuery = '';
      this.searchResults = [];
      this.showDropdown = false;
      this.selectedIndex = -1;
      this.debugInfo = '';
    },
    
    onFocus() {
      if (this.searchQuery && this.searchResults.length > 0) {
        this.showDropdown = true;
      }
    },
    
    onBlur() {
      // Delay para permitir clicks en el dropdown
      setTimeout(() => {
        this.showDropdown = false;
      }, 200);
    },
    
    onKeyDown(event) {
      if (!this.showDropdown || this.searchResults.length === 0) return;
      
      switch (event.key) {
        case 'ArrowDown':
          event.preventDefault();
          this.selectedIndex = Math.min(
            this.selectedIndex + 1,
            this.searchResults.length - 1
          );
          break;
          
        case 'ArrowUp':
          event.preventDefault();
          this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
          break;
          
        case 'Enter':
          event.preventDefault();
          if (this.selectedIndex >= 0 && this.selectedIndex < this.searchResults.length) {
            this.selectDiagnostic(this.searchResults[this.selectedIndex]);
          }
          break;
          
        case 'Escape':
          this.showDropdown = false;
          this.$refs.searchInput.blur();
          break;
      }
    },
    
    handleOutsideClick(event) {
      if (!this.$el.contains(event.target)) {
        this.showDropdown = false;
      }
    },
    
    // Métodos públicos
    focus() {
      this.$refs.searchInput.focus();
    },
    
    getDiagnosticByCodes(codes) {
      return this.activeDiagnosticData.filter(item => codes.includes(item.code));
    }
  }
};
</script>

<style scoped>
/* Estilos del componente DiagnosticSelector */
/* Usa valores directos para evitar conflictos con variables globales */

/* Contenedor principal */
.diagnostic-selector-container {
  width: 100%;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Sección de búsqueda */
.search-section {
  margin-bottom: 20px;
}

.search-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #343a40;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.search-input-container {
  position: relative;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 12px;
  color: #6c757d;
  font-size: 14px;
  z-index: 2;
}

.search-input {
  width: 100%;
  padding: 12px 40px 12px 40px;
  border: 2px solid #dee2e6;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.2s ease;
  background: white;
}

.search-input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.search-input:disabled {
  background-color: #f8f9fa;
  color: #6c757d;
  cursor: not-allowed;
}

.clear-button {
  position: absolute;
  right: 8px;
  width: 24px;
  height: 24px;
  border: none;
  background: #6c757d;
  color: white;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  transition: background-color 0.2s;
}

.clear-button:hover {
  background: #dc3545;
}

/* Dropdown de resultados */
.search-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  max-height: 300px;
  overflow-y: auto;
  margin-top: 4px;
}

.dropdown-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 20px;
  color: #6c757d;
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid #dee2e6;
  border-top: 2px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.dropdown-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 20px;
  color: #6c757d;
  font-style: italic;
}

.debug-info {
  margin-top: 8px;
  padding: 8px;
  background: #ffe6e6;
  border: 1px solid #ffcccc;
  border-radius: 4px;
  font-size: 11px;
  color: #d63384;
}

.dropdown-results {
  max-height: 250px;
  overflow-y: auto;
}

.dropdown-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  cursor: pointer;
  transition: background-color 0.2s;
  border-bottom: 1px solid rgba(222, 226, 230, 0.5);
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover,
.dropdown-item-selected {
  background-color: #e9ecef;
}

.dropdown-item-disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background-color: #f8f9fa;
}

.diagnostic-info {
  flex: 1;
}

.diagnostic-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.diagnostic-code {
  font-weight: 600;
  color: #007bff;
  font-size: 13px;
}

.diagnostic-level {
  background: #17a2b8;
  color: white;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 500;
}

.diagnostic-description {
  color: #343a40;
  font-size: 13px;
  line-height: 1.4;
  margin-bottom: 4px;
}

.diagnostic-matches {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}

.match-highlight {
  background: rgba(0, 123, 255, 0.1);
  color: #007bff;
  padding: 1px 4px;
  border-radius: 3px;
  font-size: 11px;
  font-weight: 500;
}

.already-selected-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #28a745;
  font-size: 12px;
  font-weight: 500;
}

.dropdown-footer {
  padding: 8px 16px;
  background: #f8f9fa;
  border-top: 1px solid #dee2e6;
  text-align: center;
}

.dropdown-footer small {
  color: #6c757d;
  font-size: 11px;
}

/* Sección de seleccionados */
.selected-section {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 16px;
}

.selected-title {
  margin: 0 0 16px 0;
  color: #343a40;
  font-size: 16px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

.selected-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.selected-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  transition: all 0.2s;
}

.selected-item:hover {
  border-color: #007bff;
  box-shadow: 0 2px 4px rgba(0, 123, 255, 0.1);
}

.selected-content {
  flex: 1;
}

.selected-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.selected-code {
  font-weight: 600;
  color: #007bff;
  font-size: 13px;
}

.selected-level {
  background: #17a2b8;
  color: white;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 10px;
  font-weight: 500;
}

.selected-description {
  color: #343a40;
  font-size: 13px;
  line-height: 1.4;
}

.remove-button {
  width: 28px;
  height: 28px;
  border: none;
  background: #dc3545;
  color: white;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  transition: all 0.2s;
  margin-left: 12px;
}

.remove-button:hover {
  background: #c82333;
  transform: scale(1.1);
}

.selected-actions {
  display: flex;
  justify-content: flex-end;
}

/* Estado vacío */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  background: white;
  border: 2px dashed #dee2e6;
  border-radius: 8px;
  color: #6c757d;
}

.empty-state i {
  font-size: 32px;
  margin-bottom: 12px;
  opacity: 0.5;
}

.empty-state h4 {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 500;
}

.empty-state p {
  margin: 0;
  font-size: 14px;
}

/* Botones */
.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 11px;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #dee2e6;
  color: #343a40;
}

.btn-outline:hover {
  background-color: #e9ecef;
  border-color: #6c757d;
}

/* Responsive */
@media (max-width: 768px) {
  .search-input {
    padding: 10px 36px 10px 36px;
    font-size: 16px; /* Evita zoom en iOS */
  }
  
  .selected-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .remove-button {
    align-self: flex-end;
    margin-left: 0;
  }
  
  .diagnostic-matches {
    margin-top: 4px;
  }
  
  .dropdown-item {
    padding: 16px 12px;
  }
}

@media (max-width: 480px) {
  .diagnostic-selector-container {
    font-size: 14px;
  }
  
  .selected-section {
    padding: 12px;
  }
  
  .search-dropdown {
    max-height: 250px;
  }
  
  .dropdown-item {
    padding: 12px 8px;
  }
}

/* Scrollbar personalizado para el dropdown */
.search-dropdown::-webkit-scrollbar,
.dropdown-results::-webkit-scrollbar {
  width: 6px;
}

.search-dropdown::-webkit-scrollbar-track,
.dropdown-results::-webkit-scrollbar-track {
  background: #f8f9fa;
}

.search-dropdown::-webkit-scrollbar-thumb,
.dropdown-results::-webkit-scrollbar-thumb {
  background: #dee2e6;
  border-radius: 3px;
}

.search-dropdown::-webkit-scrollbar-thumb:hover,
.dropdown-results::-webkit-scrollbar-thumb:hover {
  background: #6c757d;
}

/* Animaciones */
.search-dropdown {
  animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.selected-item {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Estados de hover mejorados */
.dropdown-item:not(.dropdown-item-disabled):hover .diagnostic-code {
  color: #007bff;
}

.selected-item:hover .selected-code {
  color: #0056b3;
}

/* Mejoras de accesibilidad */
.search-input:focus,
.remove-button:focus,
.clear-button:focus,
.btn:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
}

.dropdown-item-selected {
  background-color: rgba(0, 123, 255, 0.1);
  border-left: 3px solid #007bff;
}
