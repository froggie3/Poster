<?php

declare(strict_types=1);

namespace Iigau\Poster\Cli;

class CliParser
{
    /**
     * getopt() の値
     * 
     * クラスの中からは取得できないので外部から入れます
     */
    readonly array $opt;

    /**
     * $argv の値
     * 
     * クラスの中からは取得できないので外部から入れます
     */
    readonly array $cliArgs;

    /**
     * キャッシュを無効化する
     */
    readonly bool $isForced;

    /**
     * ヘルプを表示する
     */
    readonly bool $isHelp;

    /**
     * デバッグ用のメッセージを出力する
     */
    readonly bool $isVerbose;

    /**
     * SQLite3 のデータベースの場所
     */
    readonly string $db;

    /**
     * ヘルプメッセージ
     */
    const HELP_MESSAGE = <<<EOM
    Usage: :PROG -d|--db DATABASE [-f|--force] [-v|--verbose] [-h|--help]

    Discord bot written in PHP.

    Mandatory options:
      -d, --db=FILE   Command line flag to specify database.

    Options:
      -f, --force     Invalidate cache and force update.
      -v, --verbose   Show debug message.
      -h, --help      Show this message and exit.

    Author: Yokkin <https://yokkin.com>
    EOM;

    /**
     * 正常終了コード
     */
    const EXIT_CODE = 0;

    /**
     * コンストラクタ。
     */
    function __construct(array $cliArgs, array $opt)
    {
        $this->cliArgs = $cliArgs;
        $this->opt = $opt;

        // キーは突っ込む属性名、
        // バリューの 0 番目の要素はコールバックとして、評価された値を引数にとり実行されるメソッド名
        $flagPattern = [
            'isHelp'    => ['help', ['h', 'help']],
            'isForced'  => ['forced', ['f', 'force']],
            'isVerbose' => ['verbose', ['v', 'verbose']],
        ];

        $valuePattern = [
            'db' => ['db', ['d', 'db']]
        ];

        foreach ($flagPattern as $property => [$call, $test]) {
            $this->{$property} = $this->testTrue($this->opt, $test, $call);
        }

        foreach ($valuePattern as $property => [$call, $test]) {
            $this->{$property} = $this->testValue($this->opt, $test, $call);
        }

        $this->debug();
    }

    /**
     * ヘルプを表示。
     * 
     * `--help` で呼び出される場合は `perposed = true` して正常終了させる
     * 
     * それ以外のエラーでは適当なエラーコードが別の場所で返されるように `perposed = false`
     */
    protected function help(...$perposed): void
    {
        echo str_replace(":PROG", $this->cliArgs[0], self::HELP_MESSAGE) . "\n";
        $isHelp = array_shift($perposed);
        if ($isHelp) {
            exit(self::EXIT_CODE);
        }
    }

    protected function debug(...$result): void
    {
        // var_dump($this->opt);
        var_dump($this);
    }

    protected function forced(...$result): void
    {
        echo "force update enabled.\n";
    }

    protected function verbose(...$result): void
    {
        echo "showing logs for debugging.\n";
    }

    protected function db(...$result): void
    {
        $db = array_pop($result);
        try {
            if (is_null($db)) {
                throw new \Exception("Please specify --db option.");
            }
        } catch (\Exception $e) {
            echo $this->help(false);
            echo $e->getMessage() . "\n";
            exit(self::EXIT_CODE | 1);
        }
    }

    /**
     * コマンドライン引数が $testArgs のパターンにマッチするかを判定
     * 
     * @param array $args コマンドライン引数
     * @param array $testArgs テストする引数の名前の入った配列
     */
    protected function testTrue(array $args, array $testArgs, ?string $callback): bool
    {
        $result = false;

        if (count($testArgs) == 0) {
            throw new \Exception("少なくとも 1 つ以上の引数を指定してください");
        } elseif (count($testArgs) >= 2) {
            $result = isset($args[$testArgs[0]]);
            for ($i = 1; $i < count($testArgs); $i++) {
                $result = $result || isset($args[$testArgs[$i]]);
            }
        } else {
            $result = isset($args[$testArgs[0]]);
        }
        if (!is_null($callback)) {
            $this->$callback($result);
        }
        return $result;
    }

    /**
     * オプションから値を取得することを試みる
     * 
     * @param array $args コマンドライン引数
     * @param array $testArgs テストする引数の名前の入った配列
     * @param mixed $default テストする引数の名前の入った配列
     */
    protected function testValue(array $args, array $testArgs, ?string $callback = null, ?string $default = null): mixed
    {
        $result = null;

        if (count($testArgs) == 0) {
            throw new \Exception("少なくとも 1 つ以上の引数を指定してください");
        } elseif (count($testArgs) >= 2) {
            $result = $args[$testArgs[0]] ?? $default;
            if (!is_null($result)) {
                // 空文字が入力されるケースは今回ないのでダブルチェックとして入れとく
                if ($result !== '') {
                    return $result;
                }
            } else {
                for ($i = 1; $i < count($testArgs); $i++) {
                    $result = $result ?? $args[$testArgs[$i]] ?? $default;
                }
            }
        } else {
            $result = $args[$testArgs[0]] ?? $default;
        }
        if (!is_null($callback)) {
            $this->$callback($result);
        }
        return $result;
    }
}
