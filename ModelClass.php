 <?php
//自定义Model类，封装了PDO

class Model
{
    protected $pdo = null;
    protected $tabName; //表名
    protected $pk = "local_id"; //主键字段名
    protected $fields = array(); //当前表字段名信息
    protected $limit=null; //分页信息
    protected $where = array(); //封装搜索条件的属性
    protected $order = null; //封装排序条件的属性
    public $prefix = ''; //表前缀

    /**
     * 初始化一些参数
     * Model constructor.
     * @param $tabName {string} 表名(不带前缀)
     */
    public function __construct($tabName)
    {
        //加载数据库配置
        $database = include('conf/database.php');
        $dsn = $database['type'].
            ':host='.$database['hostname'].
            ';dbname='.$database['database'].
            ';charset='.$database['charset'].
            ';port='.$database['hostport'].';';
        $this->prefix = $database['prefix'];
        $this->tabName = $this->prefix . $tabName;
        $this->pdo = new PDO($dsn,$database['username'], $database['password']);
        //加载字段信息
        $this->loadFields();
    }

    /**
     * 获取字段信息
     */
    private function loadFields()
    {
        $sql  = "desc {$this->tabName}";
        $stmt = $this->pdo->query($sql);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($list as $vo){
            //封装字段信息
            $this->fields[] = $vo['Field'];
            //判断是否是主键
            if($vo['Key']=="PRI"){
                $this->pk = $vo['Field']; 
            }
        }
    }

    /**
     * select查询
     * @param array $fields 字段名
     * @return array
     */
    public function select($fields = [])
    {
        $fields_str = " * ";

        if(count($fields) > 0){
            $fields_str = implode($fields," , ");
        }

        $sql = "select ".$fields_str." from {$this->tabName}";
	
        //判断并封装搜索语句
        if(count($this->where)>0){
            $sql .= " where ".implode(" and ",$this->where);
        }
        //判断并封装排序条件
        if(!empty($this->order)){
            $sql .= " order by ".$this->order;
        }
        //判断并封装分页语句
        if(!empty($this->limit)){
            $sql .= " limit ".$this->limit;
        }

        $stmt = $this->pdo->query($sql);
        
        //清空搜索，分页等语句
        $this->where = array();
        $this->order = null;
        $this->limit = null;
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 执行自定义的查询语句
     * @param $sql {string} sql语句
     * @return array
     */
    public function query($sql)
    {
        //$sql = "select * from {$this->tabName}";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 执行自定义的执行语句
     * @param $sql {string} sql语句
     * @return int
     */
    public function commit($sql)
    {
        //$sql = "select * from {$this->tabName}";
        return $this->pdo->exec($sql);
    }

    /**
     * 根据id获取单条信息
     * @param $id
     * @return mixed
     */
    public function find($id)
    {
        $sql = "select * from {$this->tabName} where {$this->pk}={$id}";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 根据id删除一条信息
     * @param $id
     * @return int
     */
    public function del($id)
    {
        $sql = "delete from {$this->tabName} where {$this->pk}={$id}";
        // var_dump($sql);
        return $this->pdo->exec($sql);
    }

    /**
     * 插入一条信息
     * @param array $data 需添加的信息数组,一般使用关联数组
     * @return string
     */
    public function insert($data=array())
    {
        //判断参数若没有值则尝试采用POST中获取
        if(empty($data)){
            $data = $_POST;
        }
        $fieldlist = array(); //定义用于封装字段的变量
        $valuelist = array(); //定义用于封装值的变量
        //遍历要添加的信息并封装
        foreach($data as $k=>$v){
            //判断k是否为有效字段
            if(in_array($k,$this->fields)){
                $fieldlist[] = $k;
                $valuelist[] = "'".$v."'";
            } 
        }
        //拼装添加sql语句
        $sql = "insert into {$this->tabName}(".implode(",",$fieldlist).") values(".implode(",",$valuelist).")";
        //执行返回影响行数
        $this->pdo->exec($sql);
        return $this -> pdo -> lastInsertId();
    }

    /**
     * 修改信息
     * @param array $data
     * @return int
     */
    public function update($data=array())
    {
        //判断参数若没有值则尝试采用POST中获取
        if(empty($data)){
            $data = $_POST;
        }
        $fieldlist = array(); //定义用于存储修改信息
     
        //遍历要修改的信息并封装
        foreach($data as $k=>$v){
            //判断k是否为有效字段,并且不为主键
            if(in_array($k,$this->fields) && $k!=$this->pk){
                $fieldlist[] = "{$k}='{$v}'";
            } 
        }
        //拼装修改sql语句
        if(count($this->where)>0){
            $sql = "update {$this->tabName} set ".implode(",",$fieldlist). " where ".implode(" and ",$this->where);
        }else{
            $sql = "update {$this->tabName} set ".implode(",",$fieldlist)." where {$this->pk}={$data[$this->pk]}";
        }

        $this->where = [];
        //执行返回影响行数
        return $this->pdo->exec($sql);
    }

    /**
     * 获取表中的数据条数
     * @return mixed
     */
    public function count()
    {
        $sql = "select count(*) as num from {$this->tabName}";
        
        //判断并封装搜索语句
        if(count($this->where)>0){
            $sql .= " where ".implode(" and ",$this->where);
        }
        
        $stmt = $this->pdo->query($sql);
        $vo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $vo['num']; //返回结果
    }

    /**
     * 封装where条件,仅适用于能使用关联数据的场合,例如['id' => 1, 'name' => '老王']
     * @param $where
     * @return $this
     */
    public function where($where)
    {
        $this->where[] = $where;
        return $this;
    }

    /**
     * 排序条件
     * @param $order {string} 排序字段
     * @return $this
     */
    public function order($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * 分页
     * @param $m {int} 当n不存在时为查询条数
     * @param int $n 当n存在时,$m为当前条数,$n为每页条数
     * @return $this
     */
    public function limit($m,$n=0)
    {
        if($n==0){
            $this->limit = $m;
        }else{
            $this->limit = $m.",".$n;
        }
        return $this;
    }


    /**
     * 避免重复插入的upsert,主键相同修改,主键不同添加
     * @param $data
     * @return bool
     */
    public function upsert($data){
        //拼装一个指定参数的sql语句
        $keys = '( ';
        $values = '( ';
        $update = ' ';
        foreach($data as $k => $v){
            $keys .= " ".$k.",";
            $values .= " :".$k.",";
            $update .= " ".$k." = :".$k.",";
        }
        $keys = rtrim($keys, ",");
        $values = rtrim($values, "," );
        $update = rtrim($update, ",");
        $keys .= " ) ";
        $values .= " ) ";
        $sql = "INSERT INTO ".$this->tabName.$keys." VALUES ".$values."
    ON DUPLICATE KEY UPDATE".$update;

        try{
            //开启预处理
            $stam = $this->pdo->prepare($sql);

            //执行
            $result = $stam->execute($data);
            }catch (PDOException $e){
                echo $e->errorInfo;
        }
        if(isset($result)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 预处理模式的插入
     * @param $data
     * @return bool
     */
    public function preinsert($data){
        //拼装一个指定参数的sql语句
        $keys = '( ';
        $values = '( ';
        foreach($data as $k => $v){
            $keys .= " ".$k.",";
            $values .= " :".$k.",";
        }
        $keys = rtrim($keys, ",");
        $values = rtrim($values, "," );
        $keys .= " ) ";
        $values .= " ) ";
        $sql = "INSERT INTO ".$this->tabName.$keys." VALUES ".$values;

        //开启预处理
        $stem = $this->pdo->prepare($sql);
        echo $sql;
        //执行
        $stem->execute($data);
        echo $result = $this->pdo->lastInsertId();
        return $result ;
    }
}