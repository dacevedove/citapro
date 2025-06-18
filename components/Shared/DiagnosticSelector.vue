// Datos CIE-10 de prueba temporales (expandidos para mejor testing)
const datosPruebaCie10 = [
  {"level": 0, "code": "A00-B99", "description": "Ciertas enfermedades infecciosas y parasitarias"},
  {"level": 0, "code": "C00-D49", "description": "Tumores [neoplasias]"},
  {"level": 0, "code": "D50-D89", "description": "Enfermedades de la sangre y de los órganos hematopoyéticos"},
  {"level": 0, "code": "E00-E89", "description": "Enfermedades endocrinas, nutricionales y metabólicas"},
  {"level": 0, "code": "F01-F99", "description": "Trastornos mentales y del comportamiento"},
  {"level": 0, "code": "G00-G99", "description": "Enfermedades del sistema nervioso"},
  {"level": 0, "code": "H00-H59", "description": "Enfermedades del ojo y sus anexos"},
  {"level": 0, "code": "H60-H95", "description": "Enfermedades del oído y de la apófisis mastoides"},
  {"level": 0, "code": "I00-I99", "description": "Enfermedades del sistema circulatorio"},
  {"level": 0, "code": "J00-J99", "description": "Enfermedades del sistema respiratorio"},
  {"level": 0, "code": "K00-K95", "description": "Enfermedades del sistema digestivo"},
  {"level": 0, "code": "L00-L99", "description": "Enfermedades de la piel y del tejido subcutáneo"},
  {"level": 0, "code": "M00-M99", "description": "Enfermedades del sistema osteomuscular y del tejido conectivo"},
  {"level": 0, "code": "N00-N99", "description": "Enfermedades del sistema genitourinario"},
  {"level": 0, "code": "O00-O9A", "description": "Embarazo, parto y el puerperio"},
  {"level": 0, "code": "P00-P96", "description": "Ciertas afecciones originadas en el período perinatal"},
  {"level": 0, "code": "Q00-Q99", "description": "Malformaciones congénitas, deformidades y anomalías cromosómicas"},
  {"level": 0, "code": "R00-R99", "description": "Síntomas, signos y hallazgos anormales clínicos y de laboratorio"},
  {"level": 0, "code": "S00-T88", "description": "Traumatismos, envenenamientos y algunas otras consecuencias de causas externas"},
  {"level": 0, "code": "V00-Y99", "description": "Causas externas de morbilidad y de mortalidad"},
  {"level": 0, "code": "Z00-Z99", "description": "Factores que influyen en el estado de salud y contacto con los servicios de salud"},
  
  // Diabetes - nivel 1
  {"level": 1, "code": "E10", "description": "Diabetes mellitus insulinodependiente"},
  {"level": 1, "code": "E11", "description": "Diabetes mellitus no insulinodependiente"},
  {"level": 1, "code": "E12", "description": "Diabetes mellitus relacionada con malnutrición"},
  {"level": 1, "code": "E13", "description": "Otras diabetes mellitus especificadas"},
  {"level": 1, "code": "E14", "description": "Diabetes mellitus, no especificada"},
  
  // Diabetes - nivel 2 (más específicos)
  {"level": 2, "code": "E10.0", "description": "Diabetes mellitus insulinodependiente con coma"},
  {"level": 2, "code": "E10.1", "description": "Diabetes mellitus insulinodependiente con cetoacidosis"},
  {"level": 2, "code": "E10.2", "description": "Diabetes mellitus insulinodependiente con complicaciones renales"},
  {"level": 2, "code": "E10.3", "description": "Diabetes mellitus insulinodependiente con complicaciones oftálmicas"},
  {"level": 2, "code": "E10.4", "description": "Diabetes mellitus insulinodependiente con complicaciones neurológicas"},
  {"level": 2, "code": "E10.5", "description": "Diabetes mellitus insulinodependiente con complicaciones circulatorias periféricas"},
  {"level": 2, "code": "E10.6", "description": "Diabetes mellitus insulinodependiente con otras complicaciones especificadas"},
  {"level": 2, "code": "E10.7", "description": "Diabetes mellitus insulinodependiente con complicaciones múltiples"},
  {"level": 2, "code": "E10.8", "description": "Diabetes mellitus insulinodependiente con complicaciones no especificadas"},
  {"level": 2, "code": "E10.9", "description": "Diabetes mellitus insulinodependiente sin mención de complicación"},
  
  {"level": 2, "code": "E11.0", "description": "Diabetes mellitus no insulinodependiente con coma"},
  {"level": 2, "code": "E11.1", "description": "Diabetes mellitus no insulinodependiente con cetoacidosis"},
  {"level": 2, "code": "E11.2", "description": "Diabetes mellitus no insulinodependiente con complicaciones renales"},
  {"level": 2, "code": "E11.3", "description": "Diabetes mellitus no insulinodependiente con complicaciones oftálmicas"},
  {"level": 2, "code": "E11.4", "description": "Diabetes mellitus no insulinodependiente con complicaciones neurológicas"},
  {"level": 2, "code": "E11.5", "description": "Diabetes mellitus no insulinodependiente con complicaciones circulatorias periféricas"},
  {"level": 2, "code": "E11.6", "description": "Diabetes mellitus no insulinodependiente con otras complicaciones especificadas"},
  {"level": 2, "code": "E11.7", "description": "Diabetes mellitus no insulinodependiente con complicaciones múltiples"},
  {"level": 2, "code": "E11.8", "description": "Diabetes mellitus no insulinodependiente con complicaciones no especificadas"},
  {"level": 2, "code": "E11.9", "description": "Diabetes mellitus no insulinodependiente sin mención de complicación"},
  
  // Hipertensión
  {"level": 1, "code": "I10", "description": "Hipertensión esencial (primaria)"},
  {"level": 1, "code": "I11", "description": "Enfermedad cardíaca hipertensiva"},
  {"level": 1, "code": "I12", "description": "Enfermedad renal hipertensiva"},
  {"level": 1, "code": "I13", "description": "Enfermedad cardíaca y renal hipertensiva"},
  {"level": 1, "code": "I15", "description": "Hipertensión secundaria"},
  
  // Otros comunes
  {"level": 1, "code": "J44", "description": "Otras enfermedades pulmonares obstructivas crónicas"},
  {"level": 1, "code": "N18", "description": "Enfermedad renal crónica"},
  {"level": 1, "code": "Z51", "description": "Encuentro para otros cuidados médicos"},
  {"level": 1, "code": "M79", "description": "Otros trastornos de los tejidos blandos, no clasificados en otra parte"},
  {"level": 1, "code": "K29", "description": "Gastritis y duodenitis"},
  {"level": 1, "code": "B34", "description": "Infección viral de sitio no especificado"},
  {"level": 1, "code": "R50", "description": "Fiebre, no especificada"},
  {"level": 1, "code": "R51", "description": "Cefalea"},
  {"level": 1, "code": "R06", "description": "Anomalías de la respiración"},
  {"level": 1, "code": "R10", "description": "Dolor abdominal y pélvico"}
];

// Carga dinámica del archivo JSON con fallback inmediato a datos de prueba
async function loadCie10Data() {
  console.log('🔄 Iniciando carga de datos CIE-10...');
  
  // Usar datos de prueba inmediatamente para que el componente funcione
  console.log('📝 Usando datos de prueba CIE-10:', datosPruebaCie10.length, 'registros');
  return datosPruebaCie10;
  
  /* 
  // Código comentado para carga desde archivo - descomentar cuando esté listo
  try {
    console.log('🔄 Intentando cargar datos CIE-10...');
    
    // Intentar múltiples rutas
    const possiblePaths = [
      'https://citas.salu.pro/data/cie10.json',
      '/data/cie10.json',
      '../data/cie10.json',
      './data/cie10.json'
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
    console.warn('⚠️ No se pudo cargar cie10.json desde ninguna ubicación, usando datos de prueba:', error.message);
    console.log('📝 Datos de prueba cargados:', datosPruebaCie10.length, 'registros');
    return datosPruebaCie10;
  }
  */
}<template>
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

// Importar datos CIE-10 directamente en el componente
// Puedes cambiar esta ruta según donde tengas tu archivo JSON
let cie10Data = [];

// Datos CIE-10 de prueba temporales
const datosPruebaCie10 = [
  {"level": 0, "code": "A00-B99", "description": "Ciertas enfermedades infecciosas y parasitarias"},
  {"level": 0, "code": "C00-D49", "description": "Tumores [neoplasias]"},
  {"level": 0, "code": "D50-D89", "description": "Enfermedades de la sangre y de los órganos hematopoyéticos"},
  {"level": 0, "code": "E00-E89", "description": "Enfermedades endocrinas, nutricionales y metabólicas"},
  {"level": 0, "code": "F01-F99", "description": "Trastornos mentales y del comportamiento"},
  {"level": 0, "code": "G00-G99", "description": "Enfermedades del sistema nervioso"},
  {"level": 0, "code": "H00-H59", "description": "Enfermedades del ojo y sus anexos"},
  {"level": 0, "code": "H60-H95", "description": "Enfermedades del oído y de la apófisis mastoides"},
  {"level": 0, "code": "I00-I99", "description": "Enfermedades del sistema circulatorio"},
  {"level": 0, "code": "J00-J99", "description": "Enfermedades del sistema respiratorio"},
  {"level": 0, "code": "K00-K95", "description": "Enfermedades del sistema digestivo"},
  {"level": 0, "code": "L00-L99", "description": "Enfermedades de la piel y del tejido subcutáneo"},
  {"level": 0, "code": "M00-M99", "description": "Enfermedades del sistema osteomuscular y del tejido conectivo"},
  {"level": 0, "code": "N00-N99", "description": "Enfermedades del sistema genitourinario"},
  {"level": 0, "code": "O00-O9A", "description": "Embarazo, parto y el puerperio"},
  {"level": 0, "code": "P00-P96", "description": "Ciertas afecciones originadas en el período perinatal"},
  {"level": 0, "code": "Q00-Q99", "description": "Malformaciones congénitas, deformidades y anomalías cromosómicas"},
  {"level": 0, "code": "R00-R99", "description": "Síntomas, signos y hallazgos anormales clínicos y de laboratorio"},
  {"level": 0, "code": "S00-T88", "description": "Traumatismos, envenenamientos y algunas otras consecuencias de causas externas"},
  {"level": 0, "code": "V00-Y99", "description": "Causas externas de morbilidad y de mortalidad"},
  {"level": 0, "code": "Z00-Z99", "description": "Factores que influyen en el estado de salud y contacto con los servicios de salud"},
  {"level": 1, "code": "E10", "description": "Diabetes mellitus insulinodependiente"},
  {"level": 1, "code": "E11", "description": "Diabetes mellitus no insulinodependiente"},
  {"level": 1, "code": "E12", "description": "Diabetes mellitus relacionada con malnutrición"},
  {"level": 1, "code": "E13", "description": "Otras diabetes mellitus especificadas"},
  {"level": 1, "code": "E14", "description": "Diabetes mellitus, no especificada"},
  {"level": 1, "code": "I10", "description": "Hipertensión esencial (primaria)"},
  {"level": 1, "code": "I11", "description": "Enfermedad cardíaca hipertensiva"},
  {"level": 1, "code": "I12", "description": "Enfermedad renal hipertensiva"},
  {"level": 1, "code": "I13", "description": "Enfermedad cardíaca y renal hipertensiva"},
  {"level": 1, "code": "I15", "description": "Hipertensión secundaria"},
  {"level": 1, "code": "J44", "description": "Otras enfermedades pulmonares obstructivas crónicas"},
  {"level": 1, "code": "N18", "description": "Enfermedad renal crónica"},
  {"level": 1, "code": "Z51", "description": "Encuentro para otros cuidados médicos"}
];

// Carga dinámica del archivo JSON con fallback a datos de prueba
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
    console.warn('⚠️ No se pudo cargar cie10.json desde ninguna ubicación, usando datos de prueba:', error.message);
    console.log('📝 Datos de prueba cargados:', datosPruebaCie10.length, 'registros');
    return datosPruebaCie10;
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
      dataLoaded: false,  // Nuevo flag para saber si los datos están listos
      loadingData: true   // Nuevo flag para mostrar estado de carga inicial
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
        if (newData && newData.length > 0) {
          this.initializeFuse();
        }
      },
      immediate: true
    }
  },
  
  async mounted() {
    // Cargar datos CIE-10 si no se proporcionaron externamente
    try {
      this.loadingData = true;
      console.log('🔄 DiagnosticSelector mounted, iniciando carga de datos...');
      
      if (!this.diagnosticData) {
        cie10Data = await loadCie10Data();
        console.log('📋 Datos cargados:', cie10Data.length, 'registros');
      }
      
      this.initializeFuse();
      this.dataLoaded = true;
      console.log('✅ DiagnosticSelector listo para usar');
      
    } catch (error) {
      console.error('❌ Error al cargar datos CIE-10:', error);
      // Usar datos de prueba como fallback
      cie10Data = datosPruebaCie10;
      this.initializeFuse();
      this.dataLoaded = true;
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
      if (this.activeDiagnosticData && this.activeDiagnosticData.length > 0) {
        this.fuse = new Fuse(this.activeDiagnosticData, this.defaultFuseOptions);
      }
    },
    
    onSearchInput() {
      // Debounce la búsqueda
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }
      
      this.searchTimeout = setTimeout(() => {
        this.performSearch();
      }, 300);
    },
    
    async performSearch() {
      if (!this.searchQuery.trim()) {
        this.searchResults = [];
        this.showDropdown = false;
        return;
      }
      
      // Verificar que los datos estén cargados y Fuse esté inicializado
      if (!this.dataLoaded || !this.fuse) {
        console.log('⏳ Esperando a que se carguen los datos...');
        this.loading = true;
        this.showDropdown = true;
        
        // Esperar hasta que los datos estén cargados
        const maxWait = 5000; // 5 segundos máximo
        const startTime = Date.now();
        
        while (!this.dataLoaded && (Date.now() - startTime) < maxWait) {
          await new Promise(resolve => setTimeout(resolve, 100));
        }
        
        if (!this.dataLoaded) {
          console.error('❌ Timeout: Los datos no se cargaron a tiempo');
          this.loading = false;
          this.searchResults = [];
          return;
        }
      }
      
      if (!this.fuse) {
        console.warn('⚠️ Fuse.js aún no está inicializado después de cargar datos');
        this.initializeFuse();
        
        if (!this.fuse) {
          console.error('❌ No se pudo inicializar Fuse.js');
          this.loading = false;
          return;
        }
      }
      
      this.loading = true;
      this.showDropdown = true;
      
      try {
        // Simular delay para mostrar loading (opcional)
        await new Promise(resolve => setTimeout(resolve, 150));
        
        const fuseResults = this.fuse.search(this.searchQuery);
        console.log('🔍 Búsqueda realizada para:', this.searchQuery, '- Resultados:', fuseResults.length);
        
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
        
        this.selectedIndex = -1;
        
        // Emitir evento de búsqueda
        this.$emit('search', {
          query: this.searchQuery,
          results: this.searchResults
        });
        
      } catch (error) {
        console.error('❌ Error en la búsqueda:', error);
        this.searchResults = [];
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
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 20px;
  color: #6c757d;
  font-style: italic;
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
 color:white;
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
</style>