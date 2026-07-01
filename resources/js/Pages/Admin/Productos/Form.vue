<template>
  <AppLayout>
    <div class="container">
      <div class="admin-page fade-in">
        <Link :href="route('admin.productos.index')" class="back-link">← Volver a productos</Link>
        <h1 class="page-title">{{ producto ? 'Editar Producto' : 'Nuevo Producto' }}</h1>

        <form @submit.prevent="submit" class="form-card card">
          <div class="form-grid">
            <div class="form-group col-span-2">
              <label class="form-label">Nombre *</label>
              <input v-model="form.nombre" class="input" placeholder="Nombre del producto" />
              <p v-if="form.errors.nombre" class="form-error">{{ form.errors.nombre }}</p>
            </div>
            <div class="form-group">
              <label class="form-label">Categoría *</label>
              <select v-model="form.categoria_id" class="input">
                <option value="">Selecciona...</option>
                <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
              </select>
              <p v-if="form.errors.categoria_id" class="form-error">{{ form.errors.categoria_id }}</p>
            </div>
            <div class="form-group">
              <label class="form-label">Precio unitario (Bs.) *</label>
              <input v-model="form.precio_unitario" class="input" type="number" step="0.01" min="0" />
              <p v-if="form.errors.precio_unitario" class="form-error">{{ form.errors.precio_unitario }}</p>
            </div>
            <div class="form-group">
              <label class="form-label">Stock actual *</label>
              <input v-model="form.stock_actual" class="input" type="number" min="0" />
              <p v-if="form.errors.stock_actual" class="form-error">{{ form.errors.stock_actual }}</p>
            </div>
            <div class="form-group col-span-2">
              <label class="form-label">Imagen del producto</label>
              <div class="imagen-upload-box">
                <img v-if="previewUrl" :src="previewUrl" class="img-preview" alt="Preview" />
                <div class="imagen-inputs">
                  <label class="file-label">
                    <i class="fa-solid fa-upload"></i> Subir archivo
                    <input ref="fileInputRef" type="file" accept="image/jpeg,image/png,image/webp" class="file-input-hidden" @change="onFileChange" />
                  </label>
                  <span class="imagen-sep">— o —</span>
                  <input v-model="form.imagen_url" class="input" placeholder="Pegar URL de imagen en línea..." @input="onUrlInput" />
                </div>
              </div>
              <p v-if="form.errors.imagen" class="form-error">{{ form.errors.imagen }}</p>
            </div>
            <div class="form-group col-span-2">
              <label class="form-label">Descripción</label>
              <textarea v-model="form.descripcion" class="input" rows="4" placeholder="Descripción del producto..."></textarea>
            </div>
          </div>

          <!-- Tallas -->
          <div class="tallas-section">
            <h3>Tallas y Stock por Talla</h3>
            <div class="tallas-grid">
              <label v-for="t in tallas" :key="t.id" class="talla-check">
                <input type="checkbox" :value="t.id" v-model="tallasSeleccionadas" />
                <span>{{ t.codigo }}</span>
                <input
                  v-if="tallasSeleccionadas.includes(t.id)"
                  v-model.number="tallasStock[t.id]"
                  type="number" min="0" class="input talla-stock-input"
                  placeholder="Stock"
                />
              </label>
            </div>
          </div>

          <!-- Catálogos -->
          <div class="catalogos-section">
            <h3>Catálogos</h3>
            <div class="catalogos-grid">
              <label v-for="c in catalogos" :key="c.id" class="check-label">
                <input type="checkbox" :value="c.id" v-model="form.catalogos" />
                <span>{{ c.nombre }}</span>
              </label>
            </div>
          </div>

          <!-- Opciones -->
          <div class="opciones-section">
            <label class="check-label"><input type="checkbox" v-model="form.destacado" /> Producto destacado</label>
            <label class="check-label"><input type="checkbox" v-model="form.es_nueva_coleccion" /> Nueva colección</label>
            <label v-if="producto" class="check-label"><input type="checkbox" v-model="form.activo" /> Activo</label>
          </div>

          <button type="submit" class="btn btn-primary" :disabled="form.processing" style="margin-top:1.5rem">
            {{ form.processing ? 'Guardando...' : (producto ? 'Actualizar' : 'Crear Producto') }}
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
  producto:   { type: Object, default: null },
  categorias: { type: Array, default: () => [] },
  catalogos:  { type: Array, default: () => [] },
  tallas:     { type: Array, default: () => [] },
})

const fileInputRef = ref(null)
const previewUrl = ref(props.producto?.imagen_url || null)

const tallasSeleccionadas = ref(
  props.producto?.tallas?.map(t => t.id) || []
)
const tallasStock = ref({})
if (props.producto?.tallas) {
  props.producto.tallas.forEach(t => { tallasStock.value[t.id] = t.pivot?.stock || 0 })
}

const form = useForm({
  nombre: props.producto?.nombre || '',
  descripcion: props.producto?.descripcion || '',
  categoria_id: props.producto?.categoria_id || '',
  precio_unitario: props.producto?.precio_unitario || '',
  stock_actual: props.producto?.stock_actual ?? 0,
  imagen: null,
  imagen_url: props.producto?.imagen_url || '',
  destacado: props.producto?.destacado || false,
  es_nueva_coleccion: props.producto?.es_nueva_coleccion || false,
  activo: props.producto?.activo ?? true,
  tallas: [],
  catalogos: props.producto?.catalogos?.map(c => c.id) || [],
})

function onFileChange(e) {
  const file = e.target.files[0]
  if (!file) return
  form.imagen = file
  form.imagen_url = ''
  previewUrl.value = URL.createObjectURL(file)
}

function onUrlInput() {
  if (form.imagen_url) {
    form.imagen = null
    if (fileInputRef.value) fileInputRef.value.value = ''
    previewUrl.value = form.imagen_url
  }
}

function submit() {
  form.tallas = tallasSeleccionadas.value.map(id => ({
    id,
    stock: tallasStock.value[id] || 0,
  }))

  if (props.producto) {
    form.put(route('admin.productos.update', props.producto.id), { forceFormData: true })
  } else {
    form.post(route('admin.productos.store'), { forceFormData: true })
  }
}
</script>

<style scoped>
.admin-page { padding:2rem 0; }
.back-link { display:inline-block; margin-bottom:1rem; font-size:0.875rem; color:var(--color-accent); }
.page-title { font-size:1.75rem; margin-bottom:1.5rem; }
.form-card { max-width:800px; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:2rem; }
.col-span-2 { grid-column:span 2; }
.form-group { display:flex; flex-direction:column; gap:0.375rem; }
.form-label { font-weight:600; font-size:0.875rem; }
.form-error { color:var(--color-danger); font-size:0.8rem; }
.tallas-section, .catalogos-section, .opciones-section { margin-bottom:1.5rem; }
.tallas-section h3, .catalogos-section h3 { font-size:1rem; margin-bottom:0.75rem; }
.tallas-grid { display:flex; flex-wrap:wrap; gap:0.75rem; }
.talla-check { display:flex; align-items:center; gap:0.375rem; font-size:0.875rem; }
.talla-stock-input { width:60px; padding:0.25rem 0.5rem; font-size:0.8rem; }
.catalogos-grid { display:flex; flex-wrap:wrap; gap:1rem; }
.check-label { display:flex; align-items:center; gap:0.5rem; cursor:pointer; font-size:0.875rem; }
.opciones-section { display:flex; gap:2rem; flex-wrap:wrap; }
@media (max-width:600px) { .form-grid { grid-template-columns:1fr; } .col-span-2 { grid-column:span 1; } }

.imagen-upload-box { display:flex; gap:1rem; align-items:flex-start; flex-wrap:wrap; }
.img-preview { width:100px; height:100px; object-fit:cover; border-radius:8px; border:1px solid var(--border-color); flex-shrink:0; }
.imagen-inputs { display:flex; flex-direction:column; gap:0.5rem; flex:1; min-width:200px; }
.imagen-sep { font-size:0.8rem; color:var(--text-muted); text-align:center; }
.file-label { display:inline-flex; align-items:center; gap:0.5rem; padding:0.5rem 1rem; border:1px dashed var(--border-color); border-radius:6px; cursor:pointer; font-size:0.875rem; transition:border-color 0.2s; }
.file-label:hover { border-color:var(--color-primary); }
.file-input-hidden { display:none; }
</style>
