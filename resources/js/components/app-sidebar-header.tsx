import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const page = usePage<SharedData>();
    const branchContext = page.props.branchContext;
    const user = page.props.auth.user;
    const branches = branchContext?.branches ?? [];
    const changeBranch = (branchId: string) => {
        const [path, query = ''] = page.url.split('?');
        const params = new URLSearchParams(query);
        if (branchId) params.set('branch_id', branchId);
        else params.delete('branch_id');
        const queryString = params.toString();
        router.get(`${path}${queryString ? `?${queryString}` : ''}`, {}, { preserveState: true, preserveScroll: true });
    };
    const currentBranch = branches.find(branch => branch.id === branchContext?.selectedBranch);
    return (
        <header className="flex min-h-16 shrink-0 items-center justify-between gap-3 border-b border-[#e9e1d7] bg-[#fffefa] px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="flex items-center gap-3 text-xs text-[#81766d]">
                {branches.length > 0 && <label className="hidden items-center gap-2 sm:flex"><span className="text-[#98745e]">⌂</span><select aria-label="Current branch" className="brew-control" value={branchContext?.selectedBranch ?? ''} onChange={event => changeBranch(event.target.value)}><option value="">{user?.role === 'administrator' ? 'All branches' : currentBranch?.name ?? 'Choose branch'}</option>{branches.map(branch => <option key={branch.id} value={branch.id}>{branch.name}</option>)}</select><span className="brew-pill brew-pill-gold">{currentBranch?.code ?? (user?.role === 'administrator' ? 'Head office' : 'Branch')}</span></label>}
                <time className="hidden md:inline" dateTime={new Date().toISOString()}>{new Intl.DateTimeFormat('en-PH', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date())}</time>
                <span className="hidden h-8 w-px bg-[#ece5dc] md:block" />
                <div className="text-right"><span className="block font-semibold text-[#49372d]">{user?.name}</span><span className="capitalize">{user?.role?.replaceAll('_', ' ')}</span></div>
                <span className="grid h-8 w-8 place-items-center rounded-full bg-[#f1e9df] text-[10px] font-semibold text-[#805840]">{user?.name?.split(/\s+/).map(part => part[0]).join('').slice(0, 2).toUpperCase()}</span>
            </div>
        </header>
    );
}
