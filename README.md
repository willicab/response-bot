# response-bot
Bot simple que responde a frases predefinidas

## Configuración
1. [Crear un Bot](https://core.telegram.org/bots#3-how-do-i-create-a-bot).
1. Copiar el Token del bot en la constante TOKEN.
1. Rellenar el archivo *respuestas.json* con las frases y sus respuestas.

## respuestas.json
El siguente es un ejemplo del json

{
    "respuestas":{
        "Frase":{
            "respuesta":"text",
            "https://biologie.uni-greifswald.de/fileadmin/uni-greifswald/fakultaet/mnf/biologie/RTG_2010/logo_response_590x221.jpg":"photo",
            "BAADAQADMgADWl3oR4aOugYhuBv0Ag":"video"
        }
    },
    "ingreso":{
        "Bienvenido":"text",
        "CgADAQADCgADdHHhR4rw-Rmf0Y2mAg":"document"
    }
}

* En la sección *"ingreso"* colocaremos las frases que queremos que diga el bot cuando alguien se una al canal.
* En la sección *"respuestas"* colocaremos las frases y sus respectivas respuestas, como frase también se puede colocar una expresión regular.
* Para la respuesta se colocará primero la misma como clave y luego el tipo de respuesta como valor, los diferentes tipos posibles son:
  * text
  * photo
  * sticker
  * document
  * voice
  * audio
  * video
* Para los archivos se puede pasar el id del mismo, esta se puede obtener enviandolo como mensaje privado al bot, este responderá con el id y el tipo de respuesta
