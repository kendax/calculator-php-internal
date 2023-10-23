<?php

session_start();

/* Take the values in the input box, separate and prepare them for calculation, get the result of the calculation 
from another function performOperation() and return that result */
function calculateInput($expression) {
    // Define an associative array to map operator precedence
    $precedence = [
        '+' => 1,
        '-' => 1,
        '*' => 2,
        '/' => 2,
        '*-' => 3,
    ];

    // Create empty arrays for operators and values
    $operatorStack = [];
    $valueStack = [];

    /* Split $expression by a regular expression, return: the values at the points where it has been split, matches of the parenthesized
    group in the regular expression, the position of the first character of the substring in $expression  */
    $tokens = preg_split('/(\*\-|\+|-|\*|\/|\(|\))/', strval($expression), -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

    foreach ($tokens as $token) {
        // Remove any whitespace at the start and end of the string
        $token = trim($token);

        if (is_numeric($token)) { // If the token is a number, push it onto the value stack
            array_push($valueStack, $token);
        } elseif (in_array($token, ['+', '-', '*', '/', '*-'])) { // If the token is an operator
            while (!empty($operatorStack) && ($precedence[$token] <= $precedence[end($operatorStack)]))  { /* if $operatorStack is not 
                empty and the precedence of the current operator is less than or equal to the precedence of the operator at the end of
                the $operatorStack */
                // Pop operators from the operator stack and valuestack
                $operator = array_pop($operatorStack);
                $operand2 = array_pop($valueStack);
                $operand1 = array_pop($valueStack);

                $result = performOperation($operand1, $operand2, $operator); // Calculate values
                array_push($valueStack, $result); // Push the result to the end of the $valueStack array
            }
            // Push the current operator onto the operator stack
            array_push($operatorStack, $token);
        } elseif ($token === '(') { // If the token is an opening parenthesis, push it onto the operator stack
            array_push($operatorStack, $token);
        } elseif ($token === ')') { /* If the token is a closing parenthesis, pop operators from the operator stack
            and calculate values until an opening parenthesis is encountered */
            while (!empty($operatorStack) && end($operatorStack) !== '(') {
                $operator = array_pop($operatorStack);
                $operand2 = array_pop($valueStack);
                $operand1 = array_pop($valueStack);
                $result = performOperation($operand1, $operand2, $operator);
                array_push($valueStack, $result);
            }
            // Pop the opening parenthesis from the operator stack
            array_pop($operatorStack);
        }
    }

    // Calculate any remaining values and operators
    while (!empty($operatorStack)) {
        $operator = array_pop($operatorStack);
        $operand2 = array_pop($valueStack);
        $operand1 = array_pop($valueStack);
        $result = performOperation($operand1, $operand2, $operator);
        array_push($valueStack, $result);
    }

    // The final result should be on top of the value stack meaning; at the end of the valuestack
    return end($valueStack);
}


// Perform the actual calculation
function performOperation($operand1, $operand2, $operator) {
    switch ($operator) {
        case '*-':
            return $operand1 * ($operand2 * -1);
        case '+':
            return $operand1 + $operand2;
        case '-':
            return $operand1 - $operand2;
        case '*':
            return $operand1 * $operand2;
        case '/':
            if ($operand2 == 0) { // Prohibit division of a value by zero
                return "Can't divide by 0";
            }
            return $operand1 / $operand2;
        default:
            throw new Exception("Invalid operator: $operator");
    }
}

// Return the values from the $input array as a string
function getInputAsString($values){
	$o = "";
    foreach ($values as $value){
        $o .= $value;
    }
	return $o;
}


$input = []; // Create an empty array for the input
$currentValue = 0; // Create and initialize a variable for the currentvalue and have it as a global variable
$_SESSION["isOperator"] = false; // initialize a session variable


// Run the following code only if this page is accessed via posting a form
if($_SERVER['REQUEST_METHOD'] == "POST"){

    if(isset($_POST['input'])){
        // Return the JSON object as an associative array and store it in the $input array
        $input = json_decode($_POST['input']);  
	}
    // If the equals button has been clicked, set a session variable indicating results for a calculation have been displayed
    if(isset($_POST['equals'])) {
        $_SESSION["resultDisplayed"] = "yes";
    }

    if(isset($_POST)){
        $operatorPattern = '/(\*\-|\+|-|\*|\/|\(|\))/';
        $foundOperator = false;
        $operatorPosition = -1;
        $periodAfterOperator = false;
        $periodInInput = false;
        $periodInOperandOne = false;
        
        // Count the length of input
        if(is_countable($input)) {
            $lengthOfInput = count($input);
        }
    
        if(is_countable($input)) {
            foreach ($input as $index => $item) {
                // Check if input has a period
                if($item === ".") {
                    $periodInInput = true;
                }
                // Check if input has an operator
                if (preg_match($operatorPattern, $item)) {
                    $foundOperator = true;
                    $operatorPosition = $index; //Index of the operator in the $input array
                    break; // Stop after finding the first occurrence
                }
            }
        }
        
        // Check if there is a period after the operator
        if ($foundOperator) {
            for ($i = $operatorPosition + 1; $i < count($input); $i++) {
                if ($input[$i] === '.') {
                    $periodAfterOperator = true;
                    break; // Exit the loop after finding the first period
                }
            }
        }

        /* Iterate over each of the items posted with the form as key-value pairs where key is the name attribute of the html element and 
        value is the value attribute of the html element */
        foreach ($_POST as $key=>$value){
            $valueIsOperator = preg_match('/(\+|-|\*|\/)/',$value);

            // If the item that has been posted is an operator and results have been displayed in the input box
            if(($key == "divide" || $key == "multiply" || $key == "minus" || $key == "add") && $_SESSION["resultDisplayed"] === "yes"){
                $_SESSION["isOperator"] = true;
                $_SESSION["resultDisplayed"] = "no"; // negate this session variable
            }

            // If the percentage key has been clicked
			if($key == 'modulus'){
                $indexToSlice = $operatorPosition + 1;
                $slicedInput = array_slice($input, $indexToSlice, $lengthOfInput); // Slice the input array just after the operator till the end of the array
                $implodeToPercentage = implode($slicedInput); // Join slicedinput's elements to a string
                $toPercentage = floatval($implodeToPercentage)/100;
                $operandWithoutModulus = array_slice($input, 0, $indexToSlice); // Slice the input array from beginning to and including the operator

                // if the input box is empty
                if(empty($input)) {
                    $input = [];
                } else {
                    $firstOperatorPattern = '/(\*\-|\+|-|\*|\/|\(|\))/';
                    $secondOperatorPattern = '/(\*|\/|\(|\))/';
                    $plusInInput = false;
                    $divisionInInput = false;
                    $negativeInInput = false;

                    // Iterate over input items and set some boolean variables
                    foreach($input as $number=>$element) {
                        if(isset($_SESSION["multiAndMinus"])) {
                            $negativeInInput = true;
                        }elseif(preg_match($secondOperatorPattern, $element)) {
                            $divisionInInput = true;
                        }elseif(preg_match($firstOperatorPattern, $element)) {
                            $plusInInput = true;
                        }
                    }

                    // If the operator is either plus or minus
                    if($plusInInput){
                        $operandWithoutOperator = array_slice($input, 0, $operatorPosition); // Slice the input array from beginning to just before the operator
                        $implodedOperand = implode($operandWithoutOperator);
                        $operandWithModulus = $implodedOperand * $toPercentage;
                        array_push($operandWithoutModulus, $operandWithModulus); 
                        $currentValue = calculateInput(implode($operandWithoutModulus));
                        $input = []; // Have the input array being empty
                        $input[] = $currentValue;
                        $_SESSION["resultDisplayed"] = "yes";
                    }elseif($divisionInInput) { //If the operator is a division or multiplication sign
                        array_push($operandWithoutModulus, $toPercentage);
                        $currentValue = calculateInput(implode($operandWithoutModulus));
                        $input = [];
                        $input[] = $currentValue;
                        $_SESSION["resultDisplayed"] = "yes";
                    }elseif($negativeInInput) { //If the operators are a multiplication and a minus sign
                        array_push($operandWithoutModulus, $toPercentage);
                        $currentValue = calculateInput(implode($operandWithoutModulus));
                        $input = [];
                        $input[] = $currentValue;
                        $_SESSION["resultDisplayed"] = "yes";
                        unset($_SESSION["multiAndMinus"]);
                    }else { // if there is no operator
                        $currentValue = floatval(getInputAsString($input))/100;
                        $input = [];
                        $input[] = $currentValue;
                        $_SESSION["resultDisplayed"] = "yes";
                    }
                }
				
			 }
            elseif($key == 'equals'){ //If the equals button has been clicked
               $inputConv = implode($input);
               $currentValue = calculateInput($inputConv); //Pass along the values for calculation

               // If the Answer's first value is a period, precede it with a zero
               if (substr($currentValue, 0, 1) === '.') {
                $currentValue = "0" . $currentValue;
               } 

               $input = [];
               $input[] = $currentValue;
               $_SESSION["resultDisplayed"] = "yes";
            }elseif($key == "c"){ //If the C button has been clicked
                $input = array(); //Have the input array as empty
                $currentValue = 0;
            }elseif($key == "delete"){ //If the delete button has been clicked
                array_pop($input);
            }elseif($key != 'input'){ //If any other button other than percentage, equals,C or delete has been clicked
                $lastElement = end($input);
                $lastElementOperator = preg_match('/(\+|-|\*|\/)/', $lastElement);

                // If the length of the input array is larger than two
                if($lengthOfInput > 2) {
                    $secondLastElement = $lengthOfInput - 2;
                }
                $secondLastMultiply = false;

                // Checking if the second last element in the input array is the multiplication sign
                if(isset($secondLastElement)) {
                    if($input[$secondLastElement] == "*" ) {
                        $secondLastMultiply = true;
                    }
                }

                //Checking if there is a period in the number provided before an operator
                if($foundOperator === false && $periodInInput && $key == "period"){
                    $periodInOperandOne = true;
                }

                // If the period button has been clicked and the input box is empty, append a zero before inserting the period
                if($key == "period" && !$periodAfterOperator && !$periodInOperandOne && (!is_numeric($lastElement) || empty($input))){
                    array_push($input, "0");
                }

                // Prevent the user from starting the expression with an operator excluding minus for negative values
                if(($key == "divide" || $key == "multiply" || $key == "add") && $valueIsOperator && empty($input)){
                    $input = array();
                }elseif($lastElement == "*" && $value == "-") { //Allow the minus operator to follow the multiplication operator
                    $_SESSION["multiAndMinus"] = true;
                    $input[] = $value;
                }elseif($secondLastMultiply && $lastElement == "-" && $valueIsOperator) { /*If any operator comes immediately after
                     a multiplication sign and a minus sign, remove the latter two and insert that operator into the input box */
                    array_splice($input, -2);
                    $input[] = $value;
                }elseif($valueIsOperator && $lastElementOperator) { //Prevent two operators from following each other
                    array_pop($input);
                    $input[] = $value;
                }elseif($_SESSION["isOperator"] === true) { /*If an operator is clicked while the input box is displaying an answer 
                    use that answer for subsequent calculations as the first operand */
                    $input[] = $value;
                }elseif($_SESSION["resultDisplayed"] === "yes") { /*If any number is clicked while the input box is displaying an 
                    answer, erase and start inserting values afresh */
                    $input = array();
                    $input[] = $value;
                    $_SESSION["resultDisplayed"] = "no";
                }elseif($periodInOperandOne || ($periodAfterOperator && $key == 'period')){ //Prevent an operand from having more than one period
                    $input = $input;
                }else { //Add the value of the clicked button to the input array
                    $input[] = $value;
                }
                
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculator</title>
    <link rel="shortcut icon" href="images/calculator-logo.png" type="image/x-icon">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body>
    <div class="calculator">
        <form action="index.php" method="post">
            <input type="text" value="<?php echo getInputAsString($input); //Return the values from the $input array as a string?>" disabled>
            <input class="form-control" type="hidden" name="input" value='<?php echo json_encode($input); //Return $input array as a JSON object?>'/>
            <div class="calc-grid-4">
                <button type="submit" name="c"><i class="fa-solid fa-c " style="color: #00ff00; font-size: 20px;"></i></button>
                <button type="submit" name="divide" value="/"><i class="fa-solid fa-divide " style="color: #00ff00; font-size: 20px;"></i></button>
                <button type="submit" name="multiply" value="*"><i class="fa-solid fa-xmark " style="color: #00ff00; font-size: 20px;"></i></button>
                <button type="submit" name="delete"><span class="material-symbols-outlined" style="font-size: 30px;">backspace</span></button>
                <button type="submit" name="7" value="7" class="number">7</button>
                <button type="submit" name="8" value="8" class="number">8</button>
                <button type="submit" name="9" value="9" class="number">9</button>
                <button type="submit" name="minus" value="-"><i class="fa-solid fa-minus " style="color: #00ff00; font-size: 20px;"></i></button>
                <button type="submit" name="4" value="4" class="number">4</button>
                <button type="submit" name="5" value="5" class="number">5</button>
                <button type="submit" name="6" value="6" class="number">6</button>
                <button type="submit" name="add" value="+"><i class="fa-solid fa-plus " style="color: #00ff00; font-size: 20px;"></i></button>
            </div>  
            <div class="calc-grid-3">
                <button type="submit" name="1" value="1" class="number">1</button>
                <button type="submit" name="2" value="2" class="number">2</button>
                <button type="submit" name="3" value="3" class="number">3</button>
                <button type="submit" name="modulus" value="%"><i class="fa-light fa-percent" style="color: #000000; font-size: 20px;"></i></button>
                <button type="submit" name="zero" value="0" class="number">0</button>
                <button type="submit" name="period" value="."><i class="fa-solid fa-circle fa-2xs" style="color: #000000; font-size: 3px; margin-top: 20px;"></i></button>
            </div>
            <button type="submit" name="equals" class="equals-button"><i class="fa-solid fa-equals fa-2x" style="color: #ffffff; font-weight: bolder;"></i></button>
        </form>
    </div>
</body>
</html>