# Model
一个自定义的dao的数据库基本操作model
##食用方法 ￣ω￣=
   * **首先:** 将*conf*中的*database.php*中的配置配置好,在*Modelcalss*中填写正确的文件路径
```php
   require("前面的路径/ModelClass.php"); //引入model类
   $model = new Model('表名'); //实例化model,如果有前缀,在配置中填写过就不需要再写前缀了
```
   * **查询**
   ```php
    $select = #model->select("可以填写相应的字段名称数组"); //返回查询的结果数组
   ```
   * **添加** 有两种方式:不使用预处理的,使用预处理的添加.返回值为刚插入的数据的主键索引,一般是自增id
   ```php
    $data = ['name' => 'oldwang', 'age' => 18];
    $result = $model -> insert($data); //普通添加
    $result = $model -> preInsert($data); //带预处理的添加
   ```
   * **修改** 修改字段数据,返回受影响行数
   ```php
    $data = ['name' => 'oldwang', 'age' => 18];
    $result = $model -> update($data); //返回受影响行数
   ```
   * **删除** 暂只支持条件为主键的删除
    ```php
       $id = 1;
       $result = $model -> delete($id); //返回受影响行数
    ```
    
##注意事项:留了一大堆bug,因为是自用的,文档就不想多写了,自己想去吧.Σ(っ°Д°;)っ
* 自定义的查询语句请使用query($sql)方法
* 自定义的执行语句请使用execute($sql)方法 ,如增,删,修改
* 所有的查询中请尽量不要去查询没有结果的数据集,因为类中的pdo对象$stmt为空对象的话是不能使用fetchAll或者fetch方法的,会有报异常,一定要使用的话请做好异常处理

