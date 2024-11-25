<?php

/** @noinspection PhpSignatureMismatchDuringInheritanceInspection */

/*
 * MineAdmin is committed to providing solutions for quickly building web applications
 * Please view the LICENSE file that was distributed with this source code,
 * For the full copyright and license information.
 * Thank you very much for using MineAdmin.
 *
 * @Author X.Mo<root@imoi.cn>
 * @Link   https://gitee.com/xmo/MineAdmin
 */

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Mine\Generator;

use Core\Exception\ServiceException;
use Core\Utils\ComUtil;
use Hyperf\Collection\Collection;
use Hyperf\Support\Filesystem\Filesystem;
use Mine\Generator\Contracts\GeneratorTablesContract;
use Mine\Generator\Enums\GenerateTypeEnum;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Mine\Generator\Traits\SwaggerGeneratorTraits;

use function Hyperf\Support\env;
use function Hyperf\Support\make;

/**
 * 控制器生成
 * Class ControllerGenerator.
 */
class ControllerGenerator extends MineGenerator implements CodeGenerator {
	
	use SwaggerGeneratorTraits;
	
    protected GeneratorTablesContract $tablesContract;

    protected string $codeContent;

    protected Filesystem $filesystem;

    protected Collection $columns;

    /**
     * 设置生成信息.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function setGenInfo(GeneratorTablesContract $tablesContract): ControllerGenerator {
        $this->tablesContract = $tablesContract;
        $this->filesystem = make(Filesystem::class);
        if (empty($tablesContract->getModuleName()) || empty($tablesContract->getMenuName())) {
            throw new ServiceException(trans('setting.gen_code_edit'));
        }
        $this->columns = $tablesContract->getColumns();
        $this->setNamespace($this->tablesContract->getNamespace());
        return $this->placeholderReplace();
    }

    /**
     * 生成代码
     */
    public function generator(): void
    {
        $module = ComUtil::title($this->tablesContract->getModuleName()[0]) . mb_substr($this->tablesContract->getModuleName(), 1);
        if ($this->tablesContract->getGenerateType() === GenerateTypeEnum::ZIP) {
            $path = BASE_PATH . "/runtime/generate/php/app/{$module}/Controller/";
        } else {
            $path = BASE_PATH . "/app/{$module}/Controller/";
        }
        if (! empty($this->tablesContract->getPackageName())) {
            $path .= ComUtil::title($this->tablesContract->getPackageName()) . '/';
        }
        $this->filesystem->exists($path) || $this->filesystem->makeDirectory($path, 0755, true, true);
        $this->filesystem->put($path . "{$this->getClassName()}.php", $this->replace()->getCodeContent());
    }

    /**
     * 预览代码
     */
    public function preview(): string
    {
        return $this->replace()->getCodeContent();
    }

    /**
     * 获取生成控制器的类型.
     */
    public function getType(): string
    {
        return ucfirst($this->tablesContract->getType()->value);
    }

    /**
     * 获取业务名称.
     */
    public function getBusinessName(): string
    {
        return ComUtil::studly(str_replace(env('DB_PREFIX', ''), '', $this->tablesContract->getTableName()));
    }

    /**
     * 获取短业务名称.
     */
    public function getShortBusinessName(): string {
        return ComUtil::camel(str_replace(
			ComUtil::lower($this->tablesContract->getModuleName()),
            '',
            str_replace(env('DB_PREFIX', ''), '', $this->tablesContract->getTableName())
        ));
    }

    /**
     * 设置代码内容.
     */
    public function setCodeContent(string $content)
    {
        $this->codeContent = $content;
    }

    /**
     * 获取代码内容.
     */
    public function getCodeContent(): string
    {
        return $this->codeContent;
    }

    /**
     * 获取控制器模板地址
     */
    protected function getTemplatePath(): string
    {
        return $this->getStubDir() . 'Controller/main.stub';
    }

    /**
     * 读取模板内容.
     */
    protected function readTemplate(): string
    {
        return $this->filesystem->sharedGet($this->getTemplatePath());
    }

    /**
     * 占位符替换.
     */
    protected function placeholderReplace(): ControllerGenerator
    {
        $this->setCodeContent(str_replace(
            $this->getPlaceHolderContent(),
            $this->getReplaceContent(),
            $this->readTemplate()
        ));

        return $this;
    }

    /**
     * 获取要替换的占位符.
     */
    protected function getPlaceHolderContent(): array
    {
        return [
            '{NAMESPACE}', //命名空间
            '{COMMENT}', //类说明
            '{USE}', //引用
            '{CLASS_NAME}', //类名
            '{SERVICE}', //引用业务服务
            '{CONTROLLER_ROUTE}', //controller前置路由
            '{FUNCTIONS}', //方法集合
            '{REQUEST}', //
            '{INDEX_PERMISSION}', //首页权限
            '{RECYCLE_PERMISSION}', //回收站权限
            '{SAVE_PERMISSION}', //新增权限
            '{READ_PERMISSION}', //读取权限
            '{UPDATE_PERMISSION}', //修改权限
            '{DELETE_PERMISSION}', //删除权限
            '{REAL_DELETE_PERMISSION}', //真删权限
            '{RECOVERY_PERMISSION}', //恢复权限
            '{IMPORT_PERMISSION}', //导入权限
            '{EXPORT_PERMISSION}', //导出权限
            '{DTO_CLASS}', //dto类
            '{PK}', //主键
            '{STATUS_VALUE}',
            '{STATUS_FIELD}',
            '{NUMBER_FIELD}',
            '{NUMBER_TYPE}',
            '{NUMBER_VALUE}',
			'{MODEL_CLASS}', //模型类
			'{CONTROLLER_TAG}', //模块分类
			'{MENU_NAME}', //菜单说明
			'{INSERT_RULES}', //保存验证规则
			'{UPDATE_RULES}', //保存验证规则
			'{SEARCH_PARAMS}', //搜索规则
        ];
    }

    /**
     * 获取要替换占位符的内容.
     */
    protected function getReplaceContent(): array
    {
        return [
            $this->initNamespace(),
            $this->getComment(),
            $this->getUse(),
            $this->getClassName(),
            $this->getServiceName(),
            $this->getControllerRoute(),
            $this->getFunctions(),
            $this->getRequestName(),
			ComUtil::lower($this->tablesContract->getModuleName()) . ':' . $this->getShortBusinessName(),
            $this->getMethodRoute('recycle'),
            $this->getMethodRoute('insert'),
            $this->getMethodRoute('read'),
            $this->getMethodRoute('update'),
            $this->getMethodRoute('delete'),
            $this->getMethodRoute('realDelete'),
            $this->getMethodRoute('recovery'),
            $this->getMethodRoute('import'),
            $this->getMethodRoute('export'),
            $this->getDtoClass(),
            $this->getPk(),
            $this->getStatusValue(),
            $this->getStatusField(),
            $this->getNumberField(),
            $this->getNumberType(),
            $this->getNumberValue(),
			$this->getModelPath(),
			$this->getControllerTag(),
			$this->tablesContract->getMenuName(),
			$this->getSaveRules(true),
			$this->getSaveRules(false),
			$this->getSearchParams()
        ];
    }

    /**
     * 初始化控制器命名空间.
     */
    protected function initNamespace(): string
    {
        $namespace = $this->getNamespace() . '\Controller';
        if (! empty($this->tablesContract->getPackageName())) {
            return $namespace . '\\' . ComUtil::title($this->tablesContract->getPackageName());
        }
        return $namespace;
    }

    /**
     * 获取控制器注释.
     */
    protected function getComment(): string
    {
        return $this->tablesContract->getMenuName() . '控制器';
    }

    /**
     * 获取使用的类命名空间.
     */
    protected function getUse(): string
    {
        return <<<UseNamespace
use {$this->getNamespace()}\\Service\\{$this->getBusinessName()}Service;
UseNamespace;
    }

    /**
     * 获取控制器类名称.
     */
    protected function getClassName(): string
    {
        return $this->getBusinessName() . 'Controller';
    }

    /**
     * 获取服务类名称.
     */
    protected function getServiceName(): string
    {
        return $this->getBusinessName() . 'Service';
    }

    /**
     * 获取控制器路由.
     */
    protected function getControllerRoute(): string
    {
        return sprintf(
            '%s/%s',
            ComUtil::lower($this->tablesContract->getModuleName()),
            $this->getShortBusinessName()
        );
    }
	
	
	/**
	 * 获取模块标签
	 * @return string
	 */
	protected function getControllerTag(): string{
		return $this->tablesContract->getModuleMark() . '/' . $this->tablesContract->getMenuName();
	}
	

    protected function getFunctions(): string
    {
        $menus = $this->tablesContract->getGenerateMenus() ? explode(',', $this->tablesContract->getGenerateMenus()) : [];
        $otherMenu = [$this->tablesContract->getType()->value === 'single' ? 'singleList' : 'treeList'];
        if (in_array('recycle', $menus)) {
            $otherMenu[] = $this->tablesContract->getType()->value === 'single' ? 'singleRecycleList' : 'treeRecycleList';
            array_push($otherMenu, ...['realDelete', 'recovery']);
            unset($menus[array_search('recycle', $menus)]);
        }
        array_unshift($menus, ...$otherMenu);
        $phpCode = '';
        $path = $this->getStubDir() . 'Controller/';
        foreach ($menus as $menu) {
            $content = $this->filesystem->sharedGet($path . $menu . '.stub');
            $phpCode .= $content;
        }
        return $phpCode;
    }

    /**
     * 获取方法路由.
     */
    protected function getMethodRoute(string $route): string
    {
        return sprintf(
            '%s:%s:%s',
			ComUtil::lower($this->tablesContract->getModuleName()),
            $this->getShortBusinessName(),
            $route
        );
    }

    protected function getDtoClass(): string
    {
        return sprintf(
            '\%s\Dto\%s::class',
            $this->tablesContract->getNamespace(),
            $this->getBusinessName() . 'Dto'
        );
    }
	
	
	/**
	 * 获取搜索规则
	 * @return string search: [], operateField: []
	 */
	protected function getSearchParams(): string{
		$columns = $this->tablesContract->getColumns();
		$allows = ['neq' => '!=', 'gt' => '@>', 'gte' => '@>=', 'lt' => '@<', 'lte' => '@<=', 'like' => 'like', 'between' => 'between', 'in' => 'in', 'notin' => 'NOT IN'];
		$filterResult = []; //筛选结果集
		$keywordResult = []; //关键词搜索结果集
		
		foreach ($columns as $column) {
			if($column['is_keyword'] == 2){
				$keywordResult[] = $column['column_name'];
			}
			
			if($column['is_query'] == 2){
				if(!isset($allows[$column['query_type']]))continue;
				$operate = $allows[$column['query_type']];
				if(!isset($filterResult[$operate]))$filterResult[$operate] = [];
				
				$filterResult[$operate][] = $column['column_name'];
			}
		}
		
		//组成操作符字符串
		$operateContent = $this->outputPhpArray($filterResult, "\t");
		
		$content = 'search: '. $this->outputPhpArray($keywordResult) .', operateField: ' . $operateContent;
		
		return $content;
	}
	
	/**
	 * 获取验证规则
	 * @return string
	 */
	protected function getSaveRules($isadd = true): string{
		$result = [];
		$phpContent = '';
		$createCode = '';
		$updateCode = '';
		
		foreach ($this->columns as $column) {
			if(in_array($column['column_name'], ['id', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'])) {
				continue;
			}
			
			if(($isadd && $column['is_add'] == 1) || (!$isadd && $column['is_edit'] == 1))continue;
			
			//验证规则
			$rules = [];
			$oldField = $column['column_name'];
			if($column['is_required'] == 2)$field = '*' . $oldField; //必填
			
			//类型
			$typeRules = ['int' => 'i', 'varchar' => 's', 'tinyint' => 'i', 'char' => 's'];
			$type = $typeRules[$column['column_type']] ?? 's';
			
			//时间
			if(str_ends_with($oldField, '_time')){
				$rules[] = 'date';
				$type = 's';
			}else if($oldField == 'disabled'){
				$rules[] = 'disabled';
			}
			
			$item = $field . '/' . $column['column_comment'] . '/' . $type;
			if(!empty($rules))$item .= '/' . implode('|', $rules);
			
			$result[] = $item;
		}
		
		return $this->outputPhpArray($result);
	}
	
	
	/**
	 * 获取模型类路径
	 * @return string
	 */
	protected function getModelPath(): string {
		return sprintf(
			'Common\Model\%s\%s',
			$this->tablesContract->getModuleName(),
			$this->getBusinessName() . 'Model'
		);
	}
	
    /**
     * 生成代码表主键.
     */
    protected function getPk(): string
    {
        foreach ($this->columns as $column) {
            if ($column->is_pk == self::YES) {
                return $column->column_name;
            }
        }
        return '';
    }

    protected function getStatusValue(): string
    {
        return 'statusValue';
    }

    protected function getStatusField(): string
    {
        return 'statusName';
    }

    protected function getNumberField(): string
    {
        return 'numberName';
    }

    protected function getNumberType(): string
    {
        return 'numberType';
    }

    protected function getNumberValue(): string
    {
        return 'numberValue';
    }

    /**
     * 获取验证器.
     */
    protected function getRequestName(): string
    {
        return $this->getBusinessName() . 'Request';
    }
	
	//数组输出php格式数组字符串
	protected function outputPhpArray(array $array, string $attchTab = ""): string{
		if(empty($array))return '[]';
		if(isset($array[0]))return json_encode($array, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
		
		$attchTab .= "\t";
		$content = '[' . PHP_EOL;
		foreach ($array as $key => $item) {
			$content .= $attchTab;
			
			if(!is_int($key))$content .= '"' . $key . '" => ';
			
			if(is_array($item)){
				$content .= $this->outputPhpArray($item, $attchTab);
			}else if(is_string($item)){
				$content .= '"'. $item .'"';
			}else{
				$content .= $item;
			}
			
			$content .= "," . PHP_EOL;
		}
		$content .= "\t]";
		
		return $content;
	}
	
}
