<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<style>
    body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; }
    .wrapper { display: flex; height: 100vh; overflow: hidden; }
    
    /* Sidebar Design */
    .sidebar { width: 250px; background-color: #343a40; color: white; display: flex; flex-direction: column; }
    .sidebar h3 { text-align: center; padding: 20px 0; margin: 0; border-bottom: 1px solid #4f5962; background: #23272b; }
    .sidebar a { color: #c2c7d0; padding: 15px 20px; text-decoration: none; display: block; transition: 0.3s; }
    .sidebar a:hover { background-color: #4f5962; color: white; border-left: 4px solid #17a2b8; }
    
    /* Main Content Area */
    .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
    
    /* Header Design */
    .header { background-color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); z-index: 10; }
    .header h4 { margin: 0; color: #333; }
    .btn-logout { background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px; }
    .btn-logout:hover { background: #c82333; }
    
    /* Content Design */
    .content { padding: 20px; flex: 1; overflow-y: auto; }
    .card { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 3px solid #17a2b8; }
    
    /* Footer Design */
    .footer { background-color: white; padding: 15px 20px; text-align: center; border-top: 1px solid #dee2e6; font-size: 14px; color: #6c757d; }
</style>