#!/usr/bin/env python3
"""Auditoría no destructiva del código POS 4.9.5.6. Sin modificaciones MySQL.
   Ejecutar desde tests/ivc_pos_4956 tras instalar.  Opcional --bd para SELECT.
"""
import argparse, hashlib, json, subprocess, sys
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2]
HERE=Path(__file__).resolve().parent

def sha(p):
    h=hashlib.sha256()
    with p.open('rb') as f:
        for b in iter(lambda:f.read(65536),b''):h.update(b)
    return h.hexdigest()

def run(cmd,timeout=50):
    try:
        p=subprocess.run(cmd,text=True,capture_output=True,timeout=timeout)
        txt=(p.stdout or '')+(p.stderr or '')
        print(txt.strip() if txt.strip() else '(sin salida)')
        return p.returncode==0
    except (OSError,subprocess.TimeoutExpired) as exc:
        print('ERROR:',type(exc).__name__,str(exc));return False

def main():
    ap=argparse.ArgumentParser(description='Pruebas locales POS IVC, no realiza modificaciones')
    ap.add_argument('--bd',action='store_true',help='Ejecutar SELECT en MariaDB usando conexión privada existente')
    ap.add_argument('--php',default='/opt/lampp/bin/php',help='PHP CLI, predeterminado XAMPP Ubuntu')
    ap.add_argument('--node',default='node',help='Node.js para verificar sintaxis JavaScript')
    opts=ap.parse_args()
    root=ROOT
    if not (root/'index.php').is_file() or not (root/'Config/App/RolesAcceso.php').is_file():
        print('ERROR: ejecutar desde instalación real de IVC, dentro de tests/ivc_pos_4956.')
        return 2
    if not Path(opts.php).is_file() and '/' in opts.php: opts.php='php'
    checks=json.loads((HERE/'referencias.json').read_text())
    errores=0;warn=0
    print('\n[1/4] Integridad de archivos de la aplicación (solo lectura)')
    for file,expect in checks.items():
        p=root/file
        if not p.is_file() or p.is_symlink():
            print('[FALLO] Archivo ausente o es enlace simbólico:',file);errores+=1
        elif sha(p)!=expect:
            print('[AVISO] Archivo modificado respecto al paquete entregado:',file);warn+=1
        else: print('[OK]',file)
    print('\n[2/4] Sintaxis PHP')
    phpfiles=[x for x in checks if x.endswith('.php')]
    phpfiles+=['Views/admin/pos/index.php','Views/admin/pos/venta.php','Views/admin/pos/historial.php','Views/admin/pos/ticket.php',
               'Controllers/RecetasAdmin.php','Config/App/PosConfiguracionOptica.php']
    for file in sorted(set(phpfiles)):
        p=root/file
        if not p.is_file(): print('[FALLO] No existe',file);errores+=1;continue
        proc=subprocess.run([opts.php,'-l',str(p)],capture_output=True,text=True)
        if proc.returncode: print('[FALLO]',file,proc.stderr.strip());errores+=1
        else: print('[OK]',file)
    print('\n[3/4] Sintaxis JavaScript')
    jsfiles=['assets/js/modulos/pos-caja-v4951.js','assets/js/modulos/pos-venta-v4954.js',
             'assets/js/modulos/pos-postventa-v4955.js','assets/js/modulos/pos-activacion-v49532.js',
             'assets/js/modulos/recetas-carga-v49541.js']
    for file in jsfiles:
        p=root/file
        if not p.is_file():print('[FALLO] No existe',file);errores+=1;continue
        pr=subprocess.run([opts.node,'--check',str(p)],capture_output=True,text=True)
        if pr.returncode:print('[FALLO]',file,pr.stderr.strip());errores+=1
        else:print('[OK]',file)
    print('\n[4/4] Pruebas de cálculo y validaciones (sin DB)')
    if not run([opts.php,str(HERE/'test_servicios.php')]):errores+=1
    if opts.bd:
        print('\n[EXTRA] Auditoría MySQL con consultas SELECT (NO modifica datos)')
        if not run([opts.php,str(HERE/'auditoria_bd.php')],100):errores+=1
    else: print('\n[AVISO] No se ejecutó auditoría de datos. Repite con --bd cuando tengas el respaldo.')
    print('\nRESUMEN:',errores,'fallos;',warn,'archivos diferentes de las referencias originales.')
    if warn: print('AVISO: analiza esos cambios antes de considerar la versión equivalente a la entregada.')
    if errores:print('NO APROBADO: corregir hallazgos y ejecutar nuevamente.')
    else:print('Pruebas automáticas aprobadas. FALTAN las pruebas manuales y de concurrencia del PLAN_PRUEBAS.md.')
    return 1 if errores else 0
if __name__=='__main__':sys.exit(main())
