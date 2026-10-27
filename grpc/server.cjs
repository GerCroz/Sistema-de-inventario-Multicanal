const path = require('path');
const grpc = require('@grpc/grpc-js');
const protoLoader = require('@grpc/proto-loader');
const http = require('http');
const fs = require('fs');

const PROTO_PATH = path.join(__dirname, 'producto.proto');
const packageDefinition = protoLoader.loadSync(PROTO_PATH, {
    keepCase: true,
    longs: String,
    enums: String,
    defaults: true,
    oneofs: true
});

const protoDescriptor = grpc.loadPackageDefinition(packageDefinition);
const inventarioPackage = protoDescriptor.inventario;

/**
 * Helper to fetch product from Laravel REST API or SQLite directly as fallback
 */
async function fetchProduct(id) {
    return new Promise((resolve, reject) => {
        const req = http.get(`http://127.0.0.1:8000/api/productos/${id}`, (res) => {
            if (res.statusCode !== 200) {
                return reject(new Error(`Producto no encontrado (HTTP ${res.statusCode})`));
            }
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                try {
                    resolve(JSON.parse(data));
                } catch (e) {
                    reject(e);
                }
            });
        });

        req.on('error', (err) => {
            // Fallback: If Laravel HTTP server is not running, query SQLite database directly
            try {
                const sqlite3 = require('sqlite3').verbose();
                const dbPath = path.join(__dirname, '..', 'database', 'database.sqlite');
                if (fs.existsSync(dbPath)) {
                    const db = new sqlite3.Database(dbPath);
                    db.get('SELECT id, nombre, precio, stock FROM productos WHERE id = ?', [id], (sqlErr, row) => {
                        db.close();
                        if (sqlErr || !row) return reject(new Error('Producto no encontrado en SQLite'));
                        resolve(row);
                    });
                    return;
                }
            } catch (fallbackErr) {
                // sqlite3 not installed, reject with connection error
            }
            reject(new Error(`No se pudo conectar a Laravel en http://127.0.0.1:8000: ${err.message}`));
        });
    });
}

/**
 * Implementation of ObtenerProducto RPC
 */
async function obtenerProducto(call, callback) {
    const id = call.request.id;
    console.log(`[gRPC Server] Recibida petición ObtenerProducto para ID: ${id}`);

    try {
        const prod = await fetchProduct(id);
        const response = {
            id: Number(prod.id),
            nombre: String(prod.nombre),
            precio: parseFloat(prod.precio),
            stock: parseInt(prod.stock, 10)
        };
        console.log(`[gRPC Server] Retornando producto:`, response);
        callback(null, response);
    } catch (error) {
        console.error(`[gRPC Server] Error: ${error.message}`);
        callback({
            code: grpc.status.NOT_FOUND,
            message: error.message
        });
    }
}

function main() {
    const server = new grpc.Server();
    server.addService(inventarioPackage.ProductoService.service, {
        ObtenerProducto: obtenerProducto
    });

    const PORT = '0.0.0.0:50051';
    server.bindAsync(PORT, grpc.ServerCredentials.createInsecure(), (err, port) => {
        if (err) {
            console.error('Error al iniciar servidor gRPC:', err);
            return;
        }
        console.log(`===============================================`);
        console.log(`🚀 Servidor gRPC ejecutándose en ${PORT}`);
        console.log(`Contrato: ${PROTO_PATH}`);
        console.log(`===============================================`);
    });
}

main();
