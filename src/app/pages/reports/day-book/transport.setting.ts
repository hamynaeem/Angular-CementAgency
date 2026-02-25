import { formatNumber } from '../../../factories/utilities';

export const TransportSetting = {
  Columns: [
    { label: 'Date', fldName: 'Date' },
    { label: 'Vehicle', fldName: 'TransportName' },
    { label: 'Description', fldName: 'Description' },
    {
      label: 'Income',
      fldName: 'Income',
      sum: true,
      valueFormatter: (d) => formatNumber(d['Income']),
    },
    {
      label: 'Expense',
      fldName: 'Expense',
      sum: true,
      valueFormatter: (d) => formatNumber(d['Expense']),
    },
    { label: 'Status', fldName: 'Posted' },
  ],
  Actions: [
    { action: 'post', title: 'Post', icon: 'check', class: 'warning' },
    { action: 'edit', title: 'Edit', icon: 'pencil', class: 'primary' },
    { action: 'delete', title: 'Delete', icon: 'trash', class: 'danger' },
  ],
  Data: [],
};
