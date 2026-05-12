---
name: "Modulo Laravel Actual"
description: "Usa cuando necesites implementar o ajustar un cambio en el archivo Laravel actual o en el codigo seleccionado dentro de AgroControl"
argument-hint: "Describe el cambio que necesitas en este modulo"
agent: "agent"
model: "GPT-5 (copilot)"
---
Implementa el cambio solicitado sobre el archivo actual o el codigo seleccionado de este proyecto Laravel.

Instrucciones:
- Usa como contexto principal el archivo abierto y la seleccion activa si existe.
- Si hace falta, sigue solo las dependencias minimas necesarias para completar el cambio: ruta, controlador, request, modelo o vista relacionados.
- Haz cambios pequenos y consistentes con el estilo existente.
- Valida con la comprobacion mas acotada disponible despues de editar.
- Resume al final que cambiaste, que validaste y cualquier riesgo pendiente.

Solicitud del usuario:
${input}