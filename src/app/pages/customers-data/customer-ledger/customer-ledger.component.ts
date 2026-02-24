import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subject } from 'rxjs';
import { takeUntil } from 'rxjs/operators';
import { FormatDate, GetDateJSON, JSON2Date } from '../../../factories/utilities';
import { HttpBase } from '../../../services/httpbase.service';
import { CustomerAuthService } from '../services/customer-auth.service';

@Component({
  selector: 'app-customer-ledger',
  template: `
    <div class="container-fluid">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-gradient-primary text-white">
          <div class="d-flex align-items-center">
            <i class="fas fa-file-invoice-dollar me-2"></i>
            <h5 class="mb-0">Account Ledger</h5>
          </div>
        </div>
        <div class="card-body">
          <!-- Customer Info -->
          <div *ngIf="customer" class="row mb-4">
            <div class="col-12">
              <div class="alert alert-info">
                <strong>{{ customer.CustomerName }}</strong>
                <span class="ms-2 text-muted">{{ customer.ContactNo }}</span>
              </div>
            </div>
          </div>

          <!-- Date Filters -->
          <div class="row mb-4">
            <div class="col-md-3">
              <label class="form-label">From Date:</label>
              <div class="input-group">
                <input
                  class="form-control"
                  placeholder="yyyy-mm-dd"
                  name="dtFrom"
                  [(ngModel)]="Filter.FromDate"
                  ngbDatepicker
                  #dtFrom="ngbDatepicker"
                />
                <button
                  type="button"
                  class="btn btn-outline-secondary"
                  (click)="dtFrom.toggle()"
                >
                  <i class="fas fa-calendar"></i>
                </button>
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label">To Date:</label>
              <div class="input-group">
                <input
                  class="form-control"
                  placeholder="yyyy-mm-dd"
                  name="dtTo"
                  [(ngModel)]="Filter.ToDate"
                  ngbDatepicker
                  #dtTo="ngbDatepicker"
                />
                <button
                  type="button"
                  class="btn btn-outline-secondary"
                  (click)="dtTo.toggle()"
                >
                  <i class="fas fa-calendar"></i>
                </button>
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label">&nbsp;</label>
              <button
                type="button"
                class="btn btn-primary form-control"
                (click)="FilterData()"
                [disabled]="loading"
              >
                <span *ngIf="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                <i *ngIf="!loading" class="fas fa-search me-2"></i>
                {{ loading ? 'Loading...' : 'Filter' }}
              </button>
            </div>
            <div class="col-md-3">
              <label class="form-label">&nbsp;</label>
              <button
                type="button"
                class="btn btn-outline-secondary form-control"
                (click)="exportData()"
                [disabled]="data.length === 0"
              >
                <i class="fas fa-download me-2"></i>
                Export
              </button>
            </div>
          </div>

          <!-- Balance Summary Cards -->
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="card bg-info text-white">
                <div class="card-body text-center">
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <h6 class="mb-1">Opening Balance</h6>
                      <h4 class="mb-0">{{ customer?.OpenBalance | currency:'PKR':'symbol':'1.2-2' }}</h4>
                    </div>
                    <i class="fas fa-balance-scale fa-2x opacity-75"></i>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card bg-success text-white">
                <div class="card-body text-center">
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <h6 class="mb-1">Closing Balance</h6>
                      <h4 class="mb-0">{{ customer?.CloseBalance | currency:'PKR':'symbol':'1.2-2' }}</h4>
                    </div>
                    <i class="fas fa-chart-line fa-2x opacity-75"></i>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card" [ngClass]="getNetMovementClass()">
                <div class="card-body text-center text-white">
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <h6 class="mb-1">Net Movement</h6>
                      <h4 class="mb-0">{{ getNetMovement() | currency:'PKR':'symbol':'1.2-2' }}</h4>
                    </div>
                    <i class="fas fa-exchange-alt fa-2x opacity-75"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Ledger Table -->
          <div class="table-responsive">
            <div *ngIf="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-2 text-muted">Loading account data...</p>
            </div>

            <div *ngIf="!loading && data.length === 0" class="text-center py-4">
              <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
              <p class="text-muted">No transactions found for the selected date range.</p>
            </div>

            <ft-dynamic-table
              *ngIf="!loading && data.length > 0"
              [Settings]="setting"
              [Data]="data"
              (ClickAction)="InvNoClicked($event)">
            </ft-dynamic-table>
          </div>
        </div>
      </div>
    </div>
  `,
  styleUrls: ['./customer-ledger.component.scss']
})
export class CustomerLedgerComponent implements OnInit, OnDestroy {
  private destroy$ = new Subject<void>();

  public data: any = [];
  public customer: any = null;
  public loading: boolean = false;

  public Filter = {
    FromDate: GetDateJSON(),
    ToDate: GetDateJSON(),
  };

  setting = {
    Columns: [
      {
        label: 'Date',
        fldName: 'Date',
        type: 'date'
      },
      {
        label: 'Invoice No',
        fldName: 'RefID',
        button: {
          style: 'link',
          callback: (e: any) => { this.InvNoClicked(e) }
        },
      },
      {
        label: 'Notes',
        fldName: 'Notes',
      },
      {
        label: 'Description',
        fldName: 'Description',
      },
      {
        label: 'Debit',
        fldName: 'Debit',
        sum: true,
        type: 'currency',
        align: 'right'
      },
      {
        label: 'Credit',
        fldName: 'Credit',
        sum: true,
        type: 'currency',
        align: 'right'
      },
      {
        label: 'Balance',
        fldName: 'Balance',
        type: 'currency',
        align: 'right'
      },
    ],
    Actions: [],
    Data: [],
  };

  constructor(
    private http: HttpBase,
    private authService: CustomerAuthService
  ) {}

  ngOnInit() {
    // Set filter to current month by default
    this.Filter.FromDate.day = 1;

    // Subscribe to authentication state changes
    this.authService.authState$
      .pipe(takeUntil(this.destroy$))
      .subscribe(authState => {
        if (authState.isAuthenticated && authState.user) {
          this.customer = authState.user;
          this.FilterData();
        } else {
          this.customer = null;
          this.data = [];
        }
      });
  }

  ngOnDestroy() {
    this.destroy$.next();
    this.destroy$.complete();
  }

  FilterData() {
    if (!this.customer || !this.customer.CustomerID) {
      return;
    }

    this.loading = true;

    let filter = "Date between '" + JSON2Date(this.Filter.FromDate) + "' and '" + JSON2Date(this.Filter.ToDate) + "'";
    filter += ' and CustomerID=' + this.customer.CustomerID;

    this.http.getData('qrycustomeraccts?filter=' + filter + '&orderby=DetailID').then((r: any) => {
      this.data = r || [];

      if (this.data.length > 0) {
        // Calculate opening and closing balances
        this.customer.OpenBalance = (this.data[0].Balance - this.data[0].Debit) * 1 + this.data[0].Credit * 1;
        this.customer.CloseBalance = this.data[this.data.length - 1].Balance;

        // Add opening balance row
        this.data.unshift({
          Date: this.data[0].Date,
          Description: 'Opening Balance',
          RefID: '',
          Notes: '',
          Debit: 0,
          Credit: 0,
          Balance: this.customer.OpenBalance,
        });
      } else {
        // Handle case where no transactions in date range
        this.getOpeningBalance();
      }

      this.loading = false;
    }).catch(error => {
      console.error('Error loading account data:', error);
      this.loading = false;
      // Show error message to user if needed
    });
  }

  private getOpeningBalance() {
    if (!this.customer || !this.customer.CustomerID) {
      return;
    }

    let pastFilter = " Date < '" + JSON2Date(this.Filter.FromDate) + "'";
    pastFilter += ' and CustomerID=' + this.customer.CustomerID;

    this.http.getData('qrycustomeraccts?filter=' + pastFilter + '&orderby=DetailID desc&limit=1').then((r: any) => {
      if (r && r.length > 0) {
        this.customer.OpenBalance = r[0].Balance;
        this.customer.CloseBalance = r[0].Balance;
      } else {
        this.customer.OpenBalance = 0;
        this.customer.CloseBalance = 0;
      }

      // Add opening balance row
      this.data.unshift({
        Date: JSON2Date(this.Filter.FromDate),
        Description: 'Opening Balance',
        RefID: '',
        Notes: '',
        Debit: 0,
        Credit: 0,
        Balance: this.customer.OpenBalance,
      });
    }).catch(error => {
      console.error('Error loading opening balance:', error);
      this.customer.OpenBalance = 0;
      this.customer.CloseBalance = 0;
    });
  }

  InvNoClicked(e: any) {
    if (e && e.data && e.data.RefID && e.data.RefType) {
      if (e.data.RefType == 1) {
        this.http.PrintSaleInvoice(e.data.RefID);
      } else if (e.data.RefType == 2) {
        this.http.PrintPurchaseInvoice(e.data.RefID);
      }
    }
  }

  getNetMovement(): number {
    if (!this.customer) return 0;
    return (this.customer.CloseBalance || 0) - (this.customer.OpenBalance || 0);
  }

  getNetMovementClass(): string {
    const movement = this.getNetMovement();
    if (movement > 0) return 'bg-success';
    if (movement < 0) return 'bg-danger';
    return 'bg-warning';
  }

  exportData() {
    if (this.data.length === 0) return;

    // Create CSV content
    const headers = ['Date', 'Invoice No', 'Notes', 'Description', 'Debit', 'Credit', 'Balance'];
    const csvContent = [
      headers.join(','),
      ...this.data.map(row => [
        this.formatDate(row.Date),
        row.RefID || '',
        (row.Notes || '').replace(/,/g, ';'),
        (row.Description || '').replace(/,/g, ';'),
        row.Debit || 0,
        row.Credit || 0,
        row.Balance || 0
      ].join(','))
    ].join('\n');

    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `ledger-${this.customer?.CustomerName || 'customer'}-${JSON2Date(this.Filter.FromDate)}-to-${JSON2Date(this.Filter.ToDate)}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
  }

  private formatDate(d: any): string {
    return FormatDate(JSON2Date(d));
  }
}
