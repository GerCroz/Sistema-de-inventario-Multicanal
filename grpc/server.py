import os
import sqlite3
import requests
from concurrent import futures
import grpc

# Import generated protobuf files if present, or define simple handler
try:
    import producto_pb2
    import producto_pb2_grpc
except ImportError:
    producto_pb2 = None
    producto_pb2_grpc = None

DB_PATH = os.path.join(os.path.dirname(__file__), '..', 'database', 'database.sqlite')

class ProductoServiceServicer:
    def ObtenerProducto(self, request, context):
        prod_id = request.id
        print(f"[gRPC Python] Petición para ID: {prod_id}")
        
        # Try Laravel REST endpoint first
        try:
            res = requests.get(f"http://127.0.0.1:8000/api/productos/{prod_id}", timeout=2)
            if res.status_code == 200:
                data = res.json()
                return producto_pb2.ProductoResponse(
                    id=int(data['id']),
                    nombre=str(data['nombre']),
                    precio=float(data['precio']),
                    stock=int(data['stock'])
                )
        except Exception:
            pass

        # Fallback to SQLite directly
        if os.path.exists(DB_PATH):
            conn = sqlite3.connect(DB_PATH)
            cur = conn.cursor()
            cur.execute("SELECT id, nombre, precio, stock FROM productos WHERE id = ?", (prod_id,))
            row = cur.fetchone()
            conn.close()
            if row:
                return producto_pb2.ProductoResponse(
                    id=int(row[0]),
                    nombre=str(row[1]),
                    precio=float(row[2]),
                    stock=int(row[3])
                )

        context.set_code(grpc.StatusCode.NOT_FOUND)
        context.set_details(f"Producto con ID {prod_id} no encontrado")
        return producto_pb2.ProductoResponse()

def serve():
    if not producto_pb2_grpc:
        print("Para generar los stubs de Python ejecuta:")
        print("python -m grpc_tools.protoc -I. --python_out=. --grpc_python_out=. producto.proto")
        return

    server = grpc.server(futures.ThreadPoolExecutor(max_workers=10))
    producto_pb2_grpc.add_ProductoServiceServicer_to_server(ProductoServiceServicer(), server)
    server.add_insecure_port('[::]:50051')
    server.start()
    print("🚀 Servidor gRPC en Python escuchando en el puerto 50051")
    server.wait_for_termination()

if __name__ == '__main__':
    serve()
