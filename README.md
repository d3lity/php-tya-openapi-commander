# Php-Tuya-OpenApi-Commander
One file to send simple REST commands to Tyua-api and control your switches/lights

First, you have to make iot developer account https://iot.tuya.com/?_source=github and then do as this describes: https://developer.tuya.com/en/docs/iot/Platform_Configuration_smarthome?id=Kamcgamwoevrx
And after that you will get needed access codes from there. You have to paste them in rightful places on first lines of source file (tuya.php).

If everything works, you will get list of your connected devices with on/off-links. You may call these links anywhere from other code to switch state of your switches.

Code is developer friendly with no outside dependencies. You may use and modify as needed, like making commands available switching on different colors and/or brightness values for your lamps
## local_tuya.php
Separate file that can run by itself. For controlling devices locally.

If you don't want to write down your Tuya access-keys in code, you can just write ips.txt file on your own. It goes like this:
```
{
  "device id":{
    "ip": "ip.address.of.device",
    "name": "Name of device",
    "local_key": "Device's local key"
  },
  "other device is":{ .. }
}
```

Minimally you need 4 things to control Tuya device locally:
* ip-address
* device id
* local_key
* Data points (dps)

Local_key is used for encrypting/decrypting messages before sending to and after receiving from the device. Data points tell the device what to do like: change color, switch off, etc.

You can look code to see how messages are being built before sending. I tried to keep code simple as possible, so everyone could copy and adapt code to different languages and platforms.

This code supports now only protocol version 3.3. You can try to figure out different protocols from tinytua-project (python code) as I did.
