Set WshShell = CreateObject("WScript.Shell")
WshShell.CurrentDirectory = "c:\serv\sacolinhas\tiktok-listener"
WshShell.Run """C:\Program Files\nodejs\node.exe"" index.cjs", 0, False
