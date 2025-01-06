<?php
class CitasModel extends Query{
 
    public function __construct()
    {
        parent::__construct();
    }

   // public function getCita()
    //{
      //  $sql = "SELECT * FROM citas WHERE  ";
    //    return $this->select($sql);
   // } //function getToken

    public function registrarCita($apellidoP, $apellidoM, $nombre, $correo, $telefono ,$fecha,$id_hora,$token)
    {
        $sql = "INSERT INTO citas(apellidoP,apellidoM,nombre,correo,telefono,fecha,id_hora,token) VALUES (?,?,?,?,?,?,?,?)";
        $datos = array($apellidoP, $apellidoM, $nombre, $correo, $telefono ,$fecha,$id_hora,$token);
        $data = $this->insertar($sql, $datos);
        if ($data > 0) {
            $res = $data;
        } else {
            $res = 0;
        }
        return $res;
    } //function registroDirecto
    
    public function getToken($token)
    {
        $sql = "Select * From citas WHERE token='$token' ";
        return $this->select($sql);
    } //function getToken
    
    public function actualizarVerify($id_cita)
    {
        $sql = "UPDATE citas SET token=?,verify=?,color=? WHERE id_cita=?";
        $datos = array(null, 1, '#37b607',$id_cita);
        $data = $this->save($sql, $datos);
        if ($data == 1) {
            $res = $data;
        } else {
            $res = 0;
        }
        return $res;
    }
    
    public function getVerificar($correo)
    {
        $sql = "Select * From citas WHERE correo='$correo' ";
        return $this->select($sql);
    } //function getToken
    
    
     
    
    public function obtenerHorariosDisponibles($fechaCita) {
        $sql = "SELECT h.id_horario,DATE_FORMAT(h.horario, '%H:%i') as horario
        FROM horarios h 
        LEFT JOIN citas c 
        ON h.id_horario = c.id_hora 
        AND c.fecha = '$fechaCita' 
        AND c.estado = 1 
        WHERE c.id_hora 
        IS NULL 
        AND h.estado = 0;";
        
        return $this->selectAll($sql);
    }
    
    public function listarCitas(){
        $sql = "SELECT 
                CONCAT(apellidoP, ' ', apellidoM, ' ', nombre) AS title,
                CONCAT(fecha, 'T', DATE_FORMAT(horario, '%H:%i:%s')) AS start,
                color
            FROM citas
            INNER JOIN horarios ON citas.id_hora = horarios.id_horario";
    return $this->selectAll($sql);
    }
}
?>