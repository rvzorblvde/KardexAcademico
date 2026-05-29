<?php

class UploadHelper {
    const MAX_SIZE_KB = 2048;  // 2 MB
    const TIPOS_PERMITIDOS = ['image/jpeg', 'image/png', 'image/webp'];
    const EXTENSIONES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    
    public static function subirFoto($file, $carpeta, $identificador) {
        // No subió nada (campo vacío)
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        
        // Errores varios
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error al subir el archivo (código {$file['error']})");
        }
        
        // Tamaño
        if ($file['size'] > self::MAX_SIZE_KB * 1024) {
            throw new Exception("El archivo es muy grande. Máximo " . self::MAX_SIZE_KB . " KB.");
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        if (!in_array($mime, self::TIPOS_PERMITIDOS)) {
            throw new Exception("Tipo de archivo no permitido. Solo JPG, PNG y WebP.");
        }
        
        // Construir ruta destino
        $extension = self::EXTENSIONES[$mime];
        $nombreArchivo = $identificador . '.' . $extension;
        $destino = __DIR__ . "/../assets/uploads/{$carpeta}/" . $nombreArchivo;
        
        // Borrar foto vieja si existe con otra extensión
        foreach (self::EXTENSIONES as $ext) {
            $rutaVieja = __DIR__ . "/../assets/uploads/{$carpeta}/{$identificador}.{$ext}";
            if (file_exists($rutaVieja)) {
                @unlink($rutaVieja);
            }
        }
        
        // Mover el archivo subido
        if (!move_uploaded_file($file['tmp_name'], $destino)) {
            throw new Exception("Error al guardar el archivo en el servidor");
        }
        
        return $nombreArchivo;
    }
    
    public static function urlFoto($carpeta, $nombreArchivo) {
        if (empty($nombreArchivo)) return null;
        
        $ruta = __DIR__ . "/../assets/uploads/{$carpeta}/{$nombreArchivo}";
        if (!file_exists($ruta)) return null;
        
        return "assets/uploads/{$carpeta}/" . $nombreArchivo;
    }
}