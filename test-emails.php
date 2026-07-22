<?php
$dest   = "joao@maggiore.cl";
$nombre = "Joao";
$link   = "https://vitrinexo.com/?test=confirmar";
$tests  = [
    ["confirmacion",           ["nombre"=>$nombre,"link"=>$link]],
    ["aprobacion",             ["nombre"=>$nombre,"link"=>home_url("/ingresar/")]],
    ["rechazo",                ["nombre"=>$nombre]],
    ["verificacion_pendiente", ["nombre"=>$nombre]],
];
foreach ($tests as [$key, $data]) {
    $subj = get_option("vx_email_tpl_subject_{$key}", $key);
    $ok   = VX_Mailer::send($dest, $subj, $key, $data);
    echo $key . ":" . ($ok ? "OK" : "FAIL") . PHP_EOL;
}
