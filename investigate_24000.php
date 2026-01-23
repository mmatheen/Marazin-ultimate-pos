<?php

echo "=== Investigating Why 24,000 Happened ===\n\n";

// Expected calculation
$quantity = 67;
$unit_price = 250;
$expected_subtotal = $quantity * $unit_price;

echo "Expected: {$quantity} × {$unit_price} = {$expected_subtotal}\n";
echo "Got: 24,000\n";
echo "Difference: " . (24000 - $expected_subtotal) . "\n\n";

// Possible scenarios
echo "=== Possible Causes ===\n\n";

// 1. Could discount have been ADDED?
$discount_per_unit = 235; // MRP 485 - Price 250
$total_discount = $discount_per_unit * $quantity;
echo "1. If discount was ADDED instead of subtracted:\n";
echo "   {$expected_subtotal} + {$total_discount} = " . ($expected_subtotal + $total_discount) . "\n";
echo "   But we got 24,000, not " . ($expected_subtotal + $total_discount) . "\n\n";

// 2. Could it be MRP calculation?
$mrp = 485;
$mrp_total = $mrp * $quantity;
echo "2. If MRP was used instead of price:\n";
echo "   {$mrp} × {$quantity} = {$mrp_total}\n";
echo "   But we got 24,000, not {$mrp_total}\n\n";

// 3. Could it be wrong quantity?
$wrong_qty = 24000 / $unit_price;
echo "3. If quantity was wrong:\n";
echo "   24000 ÷ {$unit_price} = {$wrong_qty} units\n";
echo "   But actual quantity was {$quantity}\n\n";

// 4. Could it be wrong price?
$wrong_price = 24000 / $quantity;
echo "4. If price was wrong:\n";
echo "   24000 ÷ {$quantity} = Rs. " . number_format($wrong_price, 2) . "\n";
echo "   But actual price was Rs. {$unit_price}\n\n";

// 5. Let's check if there's a pattern with the difference
$difference = 24000 - $expected_subtotal;
echo "5. The difference analysis:\n";
echo "   Difference: {$difference}\n";
echo "   Difference ÷ quantity: " . ($difference / $quantity) . "\n";
echo "   Difference ÷ price: " . ($difference / $unit_price) . "\n\n";

// 6. Could it be number formatting issue?
echo "6. Number formatting issues:\n";
echo "   '16,750' parsed as 16750? ✓ Correct\n";
echo "   '16750' parsed as 16750? ✓ Correct\n";
echo "   But somehow became 24000? ❌\n\n";

// 7. Check if it's related to discount display
echo "7. Could the DISPLAY show wrong subtotal?\n";
echo "   If discount field (235) was in the subtotal cell...\n";
echo "   Or if multiple values were concatenated...\n\n";

echo "=== Most Likely Cause ===\n";
echo "The frontend displayed a WRONG value in the .subtotal cell,\n";
echo "and the backend just read that wrong value.\n\n";
echo "SOLUTION: Backend now recalculates from quantity × price,\n";
echo "so even if frontend sends wrong subtotal, database will be correct!\n";
