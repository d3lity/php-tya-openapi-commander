# php-tya-openapi-commander
One file to send simple REST commands to Tyua-api and control your switches/lights

First, you have to make iot developer account https://iot.tuya.com/?_source=github and then do as this describes: https://developer.tuya.com/en/docs/iot/Platform_Configuration_smarthome?id=Kamcgamwoevrx
And after that you will get needed access codes from there. You have to paste them in rightful places on first lines of source file (tuya.php).

If everything works, you will get list of your connected devices with on/off-links. You may call these links anywhere from other code to switch state of your switches.

Code is developer friendly with no outside dependencies. You may use and modify as needed, like making commands available switching on different colors and/or brightness values for your lamps
