<style>
    #receipt_items {
        width: 100%;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        /* font-size: 11px; */
        line-height: 1.2;
    }

    #receipt_items td,
    #receipt_items th {
        padding: 2px 0;
        vertical-align: top;
    }

    .item-name {
        font-weight: 400;
        text-transform: uppercase;
    }

    .item-meta {
        /* font-size: 10px; */
        color: #666;
        margin-top: 1px;
        line-height: 1.1;
    }

    .total-value {
        text-align: right;
        white-space: nowrap;
    }

    tr {
        margin: 0;
        padding: 0;
    }
</style>

<?php
/**
 * @var string $transaction_time
 * @var int $sale_id
 * @var string $invoice_number
 * @var string $employee
 * @var array $cart
 * @var float $discount
 * @var float $prediscount_subtotal
 * @var float $subtotal
 * @var array $taxes
 * @var float $total
 * @var array $payments
 * @var float $amount_change
 * @var string $barcode
 * @var array $config
 */
?>

<div id="receipt_wrapper" style="font-size: <?= $config['receipt_font_size'] ?>px;">
    <div id="receipt_header">
        <?php if ($config['company_logo'] != '') { ?>
            <div id="company_name">
                <img id="image" src="<?= base_url('uploads/' . esc($config['company_logo'], 'url')) ?>" alt="company_logo">
            </div>
        <?php } ?>

        <?php if ($config['receipt_show_company_name']) { ?>
            <div id="company_name"><?= nl2br(esc($config['company'])) ?></div>
        <?php } ?>

        <div id="company_address"><?= nl2br(esc($config['address'])) ?></div>
        <div id="company_phone"><?= esc($config['phone']) ?></div>
        <div id="sale_receipt"><?= lang('Sales.receipt') ?></div>
        <div id="sale_time"><?= ($transaction_time) ?></div>
    </div>

    <div id="receipt_general_info">
        <?php if (isset($customer)) { ?>
            <div id="customer"><?= lang('Customers.customer') . esc(": $customer") ?></div>
        <?php } ?>

        <div id="sale_id"><?= lang('Sales.id') . esc(": $sale_id") ?></div>

        <?php if (!empty($invoice_number)) { ?>
            <div id="invoice_number"><?= lang('Sales.invoice_number') . esc(": $invoice_number") ?></div>
        <?php } ?>

        <div id="employee"><?= lang('Employees.employee') . esc(": $employee") ?></div>
    </div>

    <table id="receipt_items">
        <tr>
            <th><?= lang('Sales.item_name') ?></th>
            <th style="text-align:right"><?= lang('Sales.total') ?></th>
        </tr>

        <?php foreach ($cart as $item): ?>
            <?php if ($item['print_option'] == PRINT_YES): ?>

                <tr>
                    <td class="item-name">
                        <?= esc(ucfirst($item['name'] . ' ' . $item['attribute_values'])) ?>

                        <div style="font-size: <?= $config['receipt_font_size'] ?>px; color: #666;">
                            <?= to_receipt_quantity($item['quantity']) ?>
                            ×
                            <?= to_currency_without_symbol($item['price']) ?>
                        </div>
                    </td>

                    <td class="total-value style=" text-align:right;">
                        <?= to_currency_without_symbol($item[($config['receipt_show_total_discount'] ? 'total' : 'discounted_total')]) ?></td>

                </tr>
                <?php if ($item['discount'] > 0) { ?>
                    <tr>
                        <td class="item-name">
                            <div style="font-size: <?= $config['receipt_font_size'] ?>px; color: #666;">
                                (
                                <?php if ($item['discount_type'] == FIXED) { ?>
                                    @
                                    <?= to_currency_without_symbol($item['discount']) . " " . lang('Sales.discount') ?>
                                <?php } elseif ($item['discount_type'] == PERCENT) { ?>
                                    <?= to_decimals($item['discount']) . " " . lang('Sales.discount_included') ?>
                                <?php } ?>
                                )
                            </div>
                        </td>

                        <td class="total-value" style="text-align:right;">
                            <?= to_currency_without_symbol($item['discounted_total'] - $item['total']) ?>
                        </td>
                    </tr>
                <?php } ?>

            <?php endif; ?>
        <?php endforeach; ?>

        <!-- SPACE -->
        <!-- <tr>
            <td colspan="2">&nbsp;</td>
        </tr> -->

        <!-- SUBTOTAL -->
        <!-- <tr> -->
        <!-- <td><?= lang('Sales.sub_total') ?></td> -->
        <!-- <td style="text-align:right"><?= to_currency_without_symbol($subtotal) ?></td> -->
        <!-- </tr> -->
        <?php if ($config['receipt_show_total_discount'] && $discount > 0) { ?>
            <tr>
                <td style="border-top: 2px solid #000000;"><?= lang('Sales.sub_total') ?></td>
                <td style="text-align: right; border-top:2px solid #000000;"><?= to_currency_without_symbol($prediscount_subtotal) ?></td>
            </tr>
            <tr>
                <td><?= lang('Sales.customer_discount') ?>:</td>
                <td class="total-value"><?= to_currency_without_symbol($discount * -1) ?></td>
            </tr>
        <?php } ?>

        <!-- TAX -->
        <?php if ($config['receipt_show_taxes']): ?>
            <?php foreach ($taxes as $tax): ?>
                <tr>
                    <td>
                        <?= (float) $tax['tax_rate'] . '% ' . $tax['tax_group'] ?>
                    </td>
                    <td style="text-align:right">
                        <?= to_currency_tax($tax['sale_tax_amount']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- TOTAL -->
        <?php $border = (!$config['receipt_show_taxes'] && !($config['receipt_show_total_discount'] && $discount > 0)); ?>
        <tr>
            <td style="font-weight:bold;<?= $border ? ' border-top: 2px solid black;' : '' ?>">
                <?= lang('Sales.total') ?></td>
            <td style="text-align:right;font-weight:bold;<?= $border ? ' border-top: 2px solid black;' : '' ?>">
                <?= to_currency_without_symbol($total) ?>
            </td>
        </tr>

        <!-- SPACE -->
        <tr>
            <td colspan="2">&nbsp;</td>
        </tr>

        <!-- PAYMENTS -->
        <?php
        $only_sale_check = false;
        $show_giftcard_remainder = false;

        foreach ($payments as $payment):
            $only_sale_check |= $payment['payment_type'] == lang('Sales.check');
            $splitpayment = explode(':', $payment['payment_type']);
            $show_giftcard_remainder |= $splitpayment[0] == lang('Sales.giftcard');
            ?>
            <tr>
                <td><?= $splitpayment[0] ?></td>
                <td style="text-align:right">
                    <?= to_currency_without_symbol($payment['payment_amount'] * -1) ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <!-- GIFT CARD BALANCE -->
        <?php if (isset($cur_giftcard_value) && $show_giftcard_remainder): ?>
            <tr>
                <td><?= lang('Sales.giftcard_balance') ?></td>
                <td style="text-align:right">
                    <?= to_currency_without_symbol($cur_giftcard_value) ?>
                </td>
            </tr>
        <?php endif; ?>

        <!-- CHANGE / DUE -->
        <tr>
            <td>
                <?= lang(
                    $amount_change >= 0
                    ? ($only_sale_check ? 'Sales.check_balance' : 'Sales.change_due')
                    : 'Sales.amount_due'
                ) ?>
            </td>
            <td style="text-align:right">
                <?= to_currency_without_symbol($amount_change) ?>
            </td>
        </tr>

    </table>

    <div id="sale_return_policy">
        <?= nl2br(esc($config['return_policy'])) ?>
    </div>

    <div id="barcode">
        <?= $barcode ?><br>
        <?= $sale_id ?>
    </div>
</div>