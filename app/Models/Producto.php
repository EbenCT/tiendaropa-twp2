<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'producto';

    protected $fillable = [
        'categoria_id', 'nombre', 'descripcion', 'precio_unitario',
        'talla', 'imagen_url', 'qr_code', 'stock_actual',
        'activo', 'destacado', 'es_nueva_coleccion',
    ];

    protected $casts = [
        'activo'            => 'boolean',
        'destacado'         => 'boolean',
        'es_nueva_coleccion'=> 'boolean',
        'precio_unitario'   => 'decimal:2',
    ];

    // ── Relaciones ──────────────────────────────────────────────────

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id')->orderBy('orden');
    }

    public function imagenPrincipal()
    {
        return $this->hasOne(ProductoImagen::class, 'producto_id')
                    ->where('es_principal', true)
                    ->orderBy('orden');
    }

    public function tallas()
    {
        return $this->belongsToMany(Talla::class, 'producto_talla', 'producto_id', 'talla_id')
                    ->withPivot('stock')
                    ->withTimestamps();
    }

    public function catalogos()
    {
        return $this->belongsToMany(Catalogo::class, 'catalogo_producto', 'producto_id', 'catalogo_id');
    }

    public function promociones()
    {
        return $this->belongsToMany(Promocion::class, 'producto_promocion', 'producto_id', 'promocion_id');
    }

    public function detallesPedido()
    {
        return $this->hasMany(DetallePedido::class, 'producto_id');
    }

    public function carritoItems()
    {
        return $this->hasMany(CarritoItem::class, 'producto_id');
    }

    public function favoritos()
    {
        return $this->hasMany(Favorito::class, 'producto_id');
    }

    // ── Accessors ───────────────────────────────────────────────────

    /** Total de unidades vendidas (para estadísticas) */
    public function getTotalVendidoAttribute(): int
    {
        return $this->detallesPedido()->sum('cantidad');
    }

    /** URL de la imagen principal o fallback a imagen_url legacy */
    public function getImagenUrlPrincipalAttribute(): string
    {
        return $this->imagenPrincipal?->url ?? $this->imagen_url ?? '';
    }
}
