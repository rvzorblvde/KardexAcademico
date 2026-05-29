<?php
session_start(); 
session_unset(); // Eliminar variables
session_destroy(); // DEstruir

header("Location: index.html");
exit();
