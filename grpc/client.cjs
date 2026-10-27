const path = require('path');
const grpc = require('@grpc/grpc-js');
const protoLoader = require('@grpc/proto-loader');
const http = require('http');

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

const client = new inventarioPackage.ProductoService(
    '127.0.0.1:50051',
    grpc.credentials.createInsecure()
);

const productId = process.argv[2] ? parseInt(process.argv[2], 10) : 1;

console.log(`\n======================================================`);
console.log(`🔍 [CONSIGNA AVANZADA] Comparativa gRPC vs REST para ID: ${productId}`);
console.log(`======================================================\n`);

// 1. Ejecutar petición gRPC
const tGrpcStart = performance.now();
client.ObtenerProducto({ id: productId }, (err, response) => {
    const tGrpcEnd = performance.now();
    const grpcDuration = (tGrpcEnd - tGrpcStart).toFixed(2);

    if (err) {
        console.error(`❌ [gRPC Error]: ${err.message}`);
    } else {
        console.log(`✅ [gRPC Response] (Protocol Buffers binario sobre HTTP/2):`);
        console.log(response);
        console.log(`⏱️  Tiempo gRPC: ${grpcDuration} ms\n`);
    }

    // 2. Ejecutar petición REST equivalente para comparar
    const tRestStart = performance.now();
    const restReq = http.get(`http://127.0.0.1:8000/api/productos/${productId}`, (res) => {
        let rawBody = '';
        res.on('data', chunk => rawBody += chunk);
        res.on('end', () => {
            const tRestEnd = performance.now();
            const restDuration = (tRestEnd - tRestStart).toFixed(2);
            const bodyBytes = Buffer.byteLength(rawBody, 'utf8');

            console.log(`✅ [REST Response] (JSON sobre HTTP/1.1):`);
            console.log(rawBody);
            console.log(`⏱️  Tiempo REST: ${restDuration} ms`);
            console.log(`📦 Tamaño JSON REST: ${bodyBytes} bytes (aprox. el triple que el payload protobuf serializado)`);
            console.log(`\n======================================================\n`);
            process.exit(0);
        });
    });

    restReq.on('error', (e) => {
        console.log(`⚠️ [REST Info]: No se pudo conectar a http://127.0.0.1:8000 (${e.message}).`);
        console.log(`(Asegúrate de tener corriendo 'php artisan serve' en otra terminal para ver la comparativa REST en vivo).`);
        process.exit(0);
    });
});
