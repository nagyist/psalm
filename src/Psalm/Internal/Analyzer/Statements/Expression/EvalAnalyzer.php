<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Taint\Sink;
use Psalm\CodeLocation;
use Psalm\Context;

/**
 * @internal
 */
class EvalAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Eval_ $stmt,
        Context $context,
        Context $global_context = null
    ) : void {
        ExpressionAnalyzer::analyze($statements_analyzer, $stmt->expr, $context);

        $expr_type = $statements_analyzer->node_data->getType($stmt->expr);

        if ($expr_type) {
            $codebase = $statements_analyzer->getCodebase();

            if ($codebase->taint) {
                $arg_location = new CodeLocation($statements_analyzer->getSource(), $stmt->expr);

                $eval_param_sink = Sink::getForMethodArgument(
                    'eval',
                    'eval',
                    0,
                    $arg_location,
                    $arg_location
                );

                $eval_param_sink->taints = [\Psalm\Type\Union::TAINTED_INPUT_TEXT];

                $codebase->taint->addSink($eval_param_sink);

                foreach ($expr_type->parent_nodes as $parent_node) {
                    $codebase->taint->addPath($parent_node, $eval_param_sink);
                }
            }
        }

        $context->check_classes = false;
        $context->check_variables = false;
    }
}

