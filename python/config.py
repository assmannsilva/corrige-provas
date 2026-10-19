import os

# Raiz do storage compartilhado com o Laravel
STORAGE_ROOT = os.environ.get("STORAGE_ROOT", "/storage/app/private")
DPI = 300

# Fração mínima de pixels escuros no interior da bolha para considerá-la marcada
OMR_MARK_DENSITY = 0.05

# Fração do brilho do papel abaixo da qual um pixel conta como tinta
OMR_DARK_FACTOR = 0.6

# Raio mínimo (px) para considerar um círculo detectado como bolha
OMR_MIN_RADIUS = 17

# Densidade abaixo da qual a resposta é considerada em branco
BLANK_INK_DENSITY = 0.005

# Parâmetros da binarização (CLAHE + adaptive threshold)
CLAHE_CLIP = 3.0
ADAPTIVE_BLOCK = 31
ADAPTIVE_C = 15
